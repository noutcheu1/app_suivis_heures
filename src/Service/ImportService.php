<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Importe des données depuis Access (CSV ou dump SQL) vers bdchaudoudoux.
 *
 * CSV :
 *   - Crée la table si elle n'existe pas (colonnes = en-têtes CSV, toutes LONGTEXT NULL).
 *   - Ajoute les colonnes manquantes si la table existe déjà.
 *   - INSERT … ON DUPLICATE KEY UPDATE si une clé primaire est fournie,
 *     sinon INSERT IGNORE.
 *
 * SQL :
 *   - Exécute chaque instruction du dump directement sur bdchaudoudoux.
 */
class ImportService
{
    public function __construct(
        private Connection $principalConnection,
    ) {}

    // ── CSV ───────────────────────────────────────────────────────────────────

    /**
     * @param  string        $table     Nom de la table cible (créée si inexistante)
     * @param  UploadedFile  $file      Fichier CSV exporté depuis Access
     * @param  string|null   $pkColumn  Colonne clé primaire (pour l'upsert)
     * @return array{table:string, created:bool, inserted:int, updated:int,
     *               skipped:int, columnsAdded:string[], errors:string[]}
     */
    public function importCsv(string $table, UploadedFile $file, ?string $pkColumn = null): array
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table); // sécurité
        if ($table === '') {
            return $this->csvResult($table, errors: ['Nom de table invalide.']);
        }

        $path   = $file->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            return $this->csvResult($table, errors: ['Impossible d\'ouvrir le fichier.']);
        }

        // Auto-détection séparateur
        $firstLine = fgets($handle);
        rewind($handle);
        $sep = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $rawHeaders = fgetcsv($handle, 0, $sep);
        if (!$rawHeaders) {
            fclose($handle);
            return $this->csvResult($table, errors: ['Fichier CSV vide ou illisible.']);
        }
        $headers = array_map('trim', $rawHeaders);

        // Crée ou met à jour le schéma de la table
        [$created, $columnsAdded] = $this->ensureTable($table, $headers, $pkColumn);

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];
        $line     = 1;

        while (($row = fgetcsv($handle, 0, $sep)) !== false) {
            $line++;
            if (count($row) < count($headers)) {
                // Compléter avec des nulls si la ligne est tronquée
                $row = array_pad($row, count($headers), '');
            }

            $data = [];
            foreach ($headers as $i => $col) {
                $val = trim($row[$i] ?? '');
                $data[$col] = ($val === '') ? null : $val;
            }

            try {
                $affected = $this->upsert($table, $data, $pkColumn);
                if ($affected === 1) $inserted++;
                else                  $updated++;
            } catch (\Throwable $e) {
                $errors[] = "Ligne $line : " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        return $this->csvResult(
            $table, $created, $inserted, $updated, $skipped, $columnsAdded, $errors
        );
    }

    // ── SQL ───────────────────────────────────────────────────────────────────

    /**
     * @return array{statements:int, errors:string[]}
     */
    /**
     * Importe un dump SQL complet en streaming (pas de chargement en mémoire).
     * Transformations appliquées à chaque statement avant exécution :
     *   - DROP TABLE    → ignoré  (protège les vues et données existantes)
     *   - CREATE TABLE  → CREATE TABLE IF NOT EXISTS
     *   - INSERT INTO   → REPLACE INTO  (gère les doublons)
     *
     * @return array{statements:int, skipped:int, errors:string[]}
     */
    public function importSql(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return ['statements' => 0, 'skipped' => 0, 'errors' => ['Impossible d\'ouvrir le fichier.']];
        }

        $errors   = [];
        $count    = 0;
        $skipped  = 0;
        $current  = '';
        $inString = false;
        $strChar  = '';
        $inMlComment = false;

        while (($line = fgets($handle)) !== false) {
            // Retire commentaires de début de ligne (--) hors string
            if (!$inString && !$inMlComment && preg_match('/^\s*--/', $line)) {
                continue;
            }

            // Parcourt caractère par caractère
            $len = strlen($line);
            for ($i = 0; $i < $len; $i++) {
                $ch = $line[$i];

                // Commentaire /* ... */
                if ($inMlComment) {
                    if ($ch === '*' && isset($line[$i + 1]) && $line[$i + 1] === '/') {
                        $inMlComment = false;
                        $i++;
                    }
                    continue;
                }

                if (!$inString && $ch === '/' && isset($line[$i + 1]) && $line[$i + 1] === '*') {
                    $inMlComment = true;
                    $i++;
                    continue;
                }

                // Commentaire inline --
                if (!$inString && $ch === '-' && isset($line[$i + 1]) && $line[$i + 1] === '-') {
                    break; // ignore reste de la ligne
                }

                if ($inString) {
                    $current .= $ch;
                    if ($ch === '\\') {
                        if ($i + 1 < $len) { $current .= $line[++$i]; }
                    } elseif ($ch === $strChar) {
                        if (isset($line[$i + 1]) && $line[$i + 1] === $strChar) {
                            $current .= $line[++$i]; // guillemet doublé
                        } else {
                            $inString = false;
                        }
                    }
                } else {
                    if ($ch === "'" || $ch === '"' || $ch === '`') {
                        $inString = true;
                        $strChar  = $ch;
                        $current .= $ch;
                    } elseif ($ch === ';') {
                        $stmt = trim($current);
                        $current = '';
                        if ($stmt === '') continue;

                        // Transformations idempotentes
                        if (preg_match('/^\s*DROP\s+(TABLE|VIEW)\b/i', $stmt)) {
                            $skipped++; // protège vues et données
                            continue;
                        }
                        $stmt = preg_replace(
                            '/\bCREATE\s+TABLE\s+(?!IF\s+NOT\s+EXISTS)/i',
                            'CREATE TABLE IF NOT EXISTS ', $stmt
                        );
                        $stmt = preg_replace('/\bINSERT\s+INTO\b/i', 'REPLACE INTO', $stmt);

                        try {
                            $this->principalConnection->executeStatement($stmt);
                            $count++;
                        } catch (\Throwable $e) {
                            $errors[] = substr($stmt, 0, 80) . ' → ' . $e->getMessage();
                        }
                    } else {
                        $current .= $ch;
                    }
                }
            }
        }

        fclose($handle);

        // Dernier statement sans ';' final
        $stmt = trim($current);
        if ($stmt !== '' && !preg_match('/^\s*DROP\s+(TABLE|VIEW)\b/i', $stmt)) {
            try {
                $this->principalConnection->executeStatement($stmt);
                $count++;
            } catch (\Throwable $e) {
                $errors[] = substr($stmt, 0, 80) . ' → ' . $e->getMessage();
            }
        }

        return ['statements' => $count, 'skipped' => $skipped, 'errors' => $errors];
    }

    // ── Schéma ────────────────────────────────────────────────────────────────

    /**
     * Crée la table si elle n'existe pas, ou ajoute les colonnes manquantes.
     * Retourne [bool $created, string[] $columnsAdded].
     */
    private function ensureTable(string $table, array $columns, ?string $pkColumn): array
    {
        $exists = $this->tableExists($table);

        if (!$exists) {
            $this->createTable($table, $columns, $pkColumn);
            return [true, []];
        }

        // Table existante : trouver les colonnes absentes
        $existing = $this->getExistingColumns($table);
        $missing  = array_diff($columns, $existing);

        foreach ($missing as $col) {
            $this->principalConnection->executeStatement(
                sprintf('ALTER TABLE `%s` ADD COLUMN `%s` LONGTEXT NULL', $table, $col)
            );
        }

        return [false, array_values($missing)];
    }

    private function tableExists(string $table): bool
    {
        $db = $this->principalConnection->getDatabase();
        $count = $this->principalConnection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
            [$db, $table]
        );
        return (int)$count > 0;
    }

    private function getExistingColumns(string $table): array
    {
        $db   = $this->principalConnection->getDatabase();
        $rows = $this->principalConnection->fetchAllAssociative(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
            [$db, $table]
        );
        return array_column($rows, 'COLUMN_NAME');
    }

    private function createTable(string $table, array $columns, ?string $pkColumn): void
    {
        $defs = [];
        foreach ($columns as $col) {
            if ($col === $pkColumn) {
                $defs[] = sprintf('`%s` VARCHAR(255) NOT NULL', $col);
            } else {
                $defs[] = sprintf('`%s` LONGTEXT NULL', $col);
            }
        }
        if ($pkColumn && in_array($pkColumn, $columns, true)) {
            $defs[] = sprintf('PRIMARY KEY (`%s`)', $pkColumn);
        }

        $this->principalConnection->executeStatement(
            sprintf('CREATE TABLE `%s` (%s) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4', $table, implode(', ', $defs))
        );
    }

    // ── Upsert ────────────────────────────────────────────────────────────────

    private function upsert(string $table, array $data, ?string $pkColumn): int
    {
        $cols   = array_keys($data);
        $quoted = array_map(fn($c) => "`$c`", $cols);
        $params = array_map(fn($c) => ":$c", $cols);

        if ($pkColumn) {
            $sets = array_map(fn($c) => "`$c` = VALUES(`$c`)", $cols);
            $sql  = sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
                $table,
                implode(', ', $quoted),
                implode(', ', $params),
                implode(', ', $sets),
            );
        } else {
            $sql = sprintf(
                'INSERT IGNORE INTO `%s` (%s) VALUES (%s)',
                $table,
                implode(', ', $quoted),
                implode(', ', $params),
            );
        }

        return (int) $this->principalConnection->executeStatement($sql, $data);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function csvResult(
        string  $table,
        bool    $created      = false,
        int     $inserted     = 0,
        int     $updated      = 0,
        int     $skipped      = 0,
        array   $columnsAdded = [],
        array   $errors       = [],
    ): array {
        return compact('table', 'created', 'inserted', 'updated', 'skipped', 'columnsAdded', 'errors');
    }
}
