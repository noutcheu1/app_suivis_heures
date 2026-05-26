<?php

namespace App\Service;

use App\Entity\Horaire\Tarif;
use App\Repository\TarifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TarifService
{
    private int $nbrPalierGE;
    private int $nbrPalierM;

    public function __construct(
        private TarifRepository        $repository,
        private EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')] string $projectDir
    ) {
        $config = json_decode((string)@file_get_contents($projectDir . '/configuration.json'), true) ?? [];
        $this->nbrPalierGE = (int)($config['nbrPalierTarifGE'] ?? 4);
        $this->nbrPalierM  = (int)($config['nbrPalierTarifM']  ?? 0);
    }

    public function getTarifActif(?\DateTimeInterface $date = null): ?Tarif
    {
        return $this->repository->findActif($date);
    }

    public function getTousLesTarifs(): array
    {
        return $this->repository->findAllOrdered();
    }

    public function tarifExiste(): bool
    {
        return $this->repository->count([]) > 0;
    }

    public function getNbrPalierGE(): int { return $this->nbrPalierGE; }
    public function getNbrPalierM(): int  { return $this->nbrPalierM; }

    /**
     * Decode alheureGE or alheureM JSON into flat form-field array.
     *
     * JSON format (4 paliers):
     *   [ ["$h >= 16","V"], ["$h < 16 && $h >= 8",4.5], ["$h < 8 && $h >= 4",5.0], ["$h < 4",5.5] ]
     *
     * Returns keys like:
     *   heures_max_{type}_1  (V threshold)
     *   heures_min_{type}_N / heures_max_{type}_N / tarif_{type}_N  (intermediate)
     *   heures_max_{type}_N / tarif_{type}_N  (last)
     */
    public function decoderPaliers(string $json, string $type, int $nbrPalier): array
    {
        $rows   = json_decode($json, true) ?? [];
        $result = [];

        if (empty($rows) || $nbrPalier === 0) {
            return $result;
        }

        // palier 1 : ["$h >= X","V"] → heures_max_{type}_1 = X
        preg_match('/\d+(?:\.\d+)?/', $rows[0][0] ?? '', $m);
        $result['heures_max_' . $type . '_1'] = $m[0] ?? 0;

        // paliers 2 .. nbrPalier-1 (intermédiaires)
        for ($i = 1; $i < count($rows) - 1; $i++) {
            preg_match_all('/\d+(?:\.\d+)?/', $rows[$i][0] ?? '', $m);
            $idx = $i + 1;
            $result['heures_max_' . $type . '_' . $idx] = $m[0][0] ?? 0;
            $result['heures_min_' . $type . '_' . $idx] = $m[0][1] ?? 0;
            $result['tarif_'      . $type . '_' . $idx] = $rows[$i][1] ?? 0;
        }

        // dernier palier : ["$h < X", taux]
        $last = count($rows) - 1;
        if ($last >= 1) {
            preg_match('/\d+(?:\.\d+)?/', $rows[$last][0] ?? '', $m);
            $result['heures_max_' . $type . '_' . $nbrPalier] = $m[0] ?? 0;
            $result['tarif_'      . $type . '_' . $nbrPalier] = $rows[$last][1] ?? 0;
        }

        return $result;
    }

    /**
     * Decode fraisGestion JSON into flat form-field array.
     *
     * JSON format:
     *   [ ["$age < 6","$h < 20",1.50], ["$age >= 6","$h < 25",2.00] ]
     *
     * Returns: gestion_heure_1, gestion_tarif_1 (âge >= 6), gestion_heure_2, gestion_tarif_2 (âge < 6)
     */
    public function decoderFraisGestion(string $json): array
    {
        $rows   = json_decode($json, true) ?? [];
        $result = ['gestion_heure_1' => 25, 'gestion_tarif_1' => 2.00, 'gestion_heure_2' => 20, 'gestion_tarif_2' => 1.50];

        foreach ($rows as $row) {
            $condition = $row[0] ?? '';
            $idx = str_contains($condition, '>=') ? 1 : 2;
            preg_match('/\d+(?:\.\d+)?/', $row[1] ?? '', $m);
            $result['gestion_heure_' . $idx] = $m[0] ?? 0;
            $result['gestion_tarif_' . $idx] = $row[2] ?? 0;
        }

        return $result;
    }

    /**
     * Build form-default array from active tarif (to pre-fill the form).
     */
    public function getFormDefaults(?Tarif $tarif): array
    {
        if (!$tarif) {
            return [];
        }

        return array_merge(
            $this->decoderPaliers($tarif->getAlheureGe() ?? '[]',    'GE', $this->nbrPalierGE),
            $this->decoderPaliers($tarif->getAlheureM()  ?? '[]',    'M',  $this->nbrPalierM),
            $this->decoderFraisGestion($tarif->getFraisGestion() ?? '[]'),
            [
                'parIntervention'    => $tarif->getParIntervention(),
                'maxParIntervention' => $tarif->getMaxParIntervention(),
                'kmEnfants'          => $tarif->getKmEnfants(),
                'abonnement'         => $tarif->getAbonnement(),
            ]
        );
    }

    /**
     * Persist a new horodated tarif version from POST data.
     * Never modifies an existing row — always inserts a new one.
     */
    public function creerNouveauTarif(array $data): Tarif
    {
        $tarif = new Tarif();

        // dateDebut format YYYY-MM (HTML <input type="month"> submits that)
        $dateDebut = trim($data['dateDebut'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}$/', $dateDebut)) {
            $dateDebut = date('Y-m');
        }
        $tarif->setDateDebut($dateDebut);

        $tarif->setAlheureGe($this->encoderAlheure($data, 'GE', $this->nbrPalierGE));
        $tarif->setAlheureM($this->encoderAlheure($data, 'M',  $this->nbrPalierM));
        $tarif->setFraisGestion($this->encoderFraisGestion($data));
        $tarif->setParIntervention(number_format((float)($data['parIntervention'] ?? 0), 2, '.', ''));
        $tarif->setMaxParIntervention(number_format((float)($data['maxParIntervention'] ?? 0), 2, '.', ''));
        $tarif->setKmEnfants(number_format((float)($data['kmEnfants'] ?? 0), 2, '.', ''));
        $tarif->setAbonnement(number_format((float)($data['abonnement'] ?? 0), 2, '.', ''));

        $this->entityManager->persist($tarif);
        $this->entityManager->flush();

        return $tarif;
    }

    // ── Encodeurs privés ──────────────────────────────────────────────────────

    private function encoderAlheure(array $data, string $type, int $nbrPalier): string
    {
        if ($nbrPalier === 0) {
            return json_encode([['$h >= 0', 'V'], ['$h < 0', 0.0]], JSON_UNESCAPED_UNICODE);
        }

        $paliers = [];

        // palier 1 : >= seuil → V (taux variable)
        $seuil   = (float)($data['heures_max_' . $type . '_1'] ?? 16);
        $paliers[] = ['$h >= ' . $seuil, 'V'];

        // paliers 2 .. nbrPalier-1 (intermédiaires)
        for ($i = 2; $i < $nbrPalier; $i++) {
            $max  = (float)($data['heures_max_' . $type . '_' . $i] ?? 0);
            $min  = (float)($data['heures_min_' . $type . '_' . $i] ?? 0);
            $taux = (float)($data['tarif_' . $type . '_' . $i] ?? 0);
            $paliers[] = ['$h < ' . $max . ' && $h >= ' . $min, $taux];
        }

        // dernier palier : < seuil bas → taux
        if ($nbrPalier >= 2) {
            $seuilBas = (float)($data['heures_max_' . $type . '_' . $nbrPalier] ?? 0);
            $taux     = (float)($data['tarif_' . $type . '_' . $nbrPalier] ?? 0);
            $paliers[] = ['$h < ' . $seuilBas, $taux];
        }

        return json_encode($paliers, JSON_UNESCAPED_UNICODE);
    }

    private function encoderFraisGestion(array $data): string
    {
        $h1 = (float)($data['gestion_heure_1'] ?? 25);
        $t1 = (float)($data['gestion_tarif_1'] ?? 2.00);
        $h2 = (float)($data['gestion_heure_2'] ?? 20);
        $t2 = (float)($data['gestion_tarif_2'] ?? 1.50);

        return json_encode([
            ['$age < 6',  '$h < ' . $h2, $t2],
            ['$age >= 6', '$h < ' . $h1, $t1],
        ], JSON_UNESCAPED_UNICODE);
    }
}
