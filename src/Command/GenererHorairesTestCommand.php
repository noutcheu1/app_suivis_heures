<?php

namespace App\Command;

use App\Entity\Horaire\Horaireinter;
use App\Repository\HoraireinterRepository;
use App\Repository\ParentFamilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generer-horaires-test',
    description: 'Déclare les heures pour chaque intervenant et chaque famille selon proposer',
)]
class GenererHorairesTestCommand extends Command
{
    private EntityManagerInterface $emHoraire;

    public function __construct(
        private HoraireinterRepository $horaireRepo,
        private ParentFamilleRepository $parentRepo,
        private ManagerRegistry        $doctrine,
    ) {
        parent::__construct();
        $this->emHoraire = $doctrine->getManager('horaire');
    }

    protected function configure(): void
    {
        $this->addOption('semaines', 's', InputOption::VALUE_OPTIONAL, 'Nombre de semaines (défaut: 4)', 4);
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simuler sans insérer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $semaines = (int)$input->getOption('semaines');
        $dryRun   = (bool)$input->getOption('dry-run');

        $fin   = new \DateTime('yesterday');
        $debut = (clone $fin)->modify("-{$semaines} weeks +1 day");

        $io->title('Génération des heures de test');
        $io->text([
            "Période : {$debut->format('d/m/Y')} → {$fin->format('d/m/Y')}",
            $dryRun ? '[DRY-RUN]' : '[INSERTION]',
        ]);

        $connPrincipal = $this->doctrine->getConnection('principal');

        // Récupérer tous les proposer actifs avec nom parent (= nomFam stocké dans horaireinter)
        // Le nomFam DOIT correspondre à ce que famille_label() retourne, sinon getReleveData()
        // groupe toutes les familles sous la même clé dans famillesMap
        $rows = $connPrincipal->executeQuery("
            SELECT
                p.numSalarie_Intervenants         AS numInter,
                p.numero_Famille                  AS numFam,
                COALESCE(
                    (SELECT CONCAT(pa.nom_Parents, ' ', pa.prenom_Parents)
                     FROM parents pa
                     WHERE pa.numero_Famille = p.numero_Famille
                     LIMIT 1),
                    f.Famille_Famille,
                    p.numero_Famille
                )                                 AS nomFam,
                p.idPresta_Prestations            AS typePresta,
                p.jour_Proposer                   AS jour,
                p.hDeb_Proposer                   AS hDeb,
                p.hFin_Proposer                   AS hFin,
                COALESCE(p.frequence_Proposer, 1) AS frequence,
                p.DateDeb_Proposer                AS dateDeb
            FROM proposer p
            INNER JOIN intervenants i ON i.numSalarie_Intervenants = p.numSalarie_Intervenants
            INNER JOIN famille f      ON f.numero_Famille = p.numero_Famille
            WHERE
              -- Seulement les prestataires
              p.idADH_TypeADH = 'PREST'
              -- Proposer actif sur la période
              
              AND p.DateDeb_Proposer <= :fin
              AND (p.dateFin_Proposer IS NULL
                   OR p.dateFin_Proposer = '0000-00-00'
                   OR p.dateFin_Proposer >= :debut)
              AND p.hFin_Proposer IS NOT NULL
              AND p.hFin_Proposer != '00:00:00'
              -- Intervenant actif (non archivé, dans ses dates)
              AND (i.archive_Intervenants = 0 OR i.archive_Intervenants IS NULL)
              AND (i.dateEntree_Intervenants IS NULL OR i.dateEntree_Intervenants <= :fin)
              AND (i.dateSortie_Intervenants IS NULL
                   OR i.dateSortie_Intervenants = '0000-00-00'
                   OR i.dateSortie_Intervenants >= :debut)
              -- Famille active (non archivée, dans ses dates, hors 9999)
              AND (f.archive_Famille = 0 OR f.archive_Famille IS NULL)
               AND (p.idPresta_Prestations = 'MENA' OR p.idPresta_Prestations = 'ENFA')
              AND (f.dateEntree_Famille IS NULL OR f.dateEntree_Famille <= :fin)
              AND (f.dateSortie_Famille IS NULL
                   OR f.dateSortie_Famille = '0000-00-00'
                   OR f.dateSortie_Famille >= :debut)
              AND f.numero_Famille != '9999'
              -- Famille prestataire pour le bon type (MENA → prestM, ENFA → prestGE)
              AND (
                  (p.idPresta_Prestations = 'MENA' AND f.prestM_Famille  = 1)
               OR (p.idPresta_Prestations != 'MENA' AND f.prestGE_Famille = 1)
              )
            ORDER BY p.numSalarie_Intervenants, p.numero_Famille, p.jour_Proposer, p.hDeb_Proposer
        ", [
            'debut' => $debut->format('Y-m-d'),
            'fin'   => $fin->format('Y-m-d'),
        ])->fetchAllAssociative();

        $io->text(count($rows) . ' lignes proposer trouvées');

        $jourMap = [
            'lundi' => 1, 'mardi' => 2, 'mercredi' => 3,
            'jeudi' => 4, 'vendredi' => 5, 'samedi' => 6, 'dimanche' => 7,
        ];

        $created = 0;
        $skipped = 0;
        $batch   = 0;

        $current = clone $debut;
        while ($current <= $fin) {
            $dowNum  = (int)$current->format('N');
            $dowName = array_search($dowNum, $jourMap, true);

            foreach ($rows as $row) {
                if (mb_strtolower(trim($row['jour'])) !== $dowName) continue;

                $numInter   = (int)$row['numInter'];
                $numFam     = (string)$row['numFam'];
                $nomFam     = (string)$row['nomFam'];  // label identique à famille_label()
                $typeRaw    = strtoupper($row['typePresta']);
                $typePresta = (str_contains($typeRaw, 'MENA') || $typeRaw === 'M') ? 'MENA' : 'ENFA';
                $hDeb       = substr($row['hDeb'], 0, 5);
                $hFin       = substr($row['hFin'], 0, 5);
                $frequence  = max(1, (int)$row['frequence']);

                // Respecter la fréquence bi-hebdomadaire etc.
                if ($frequence > 1) {
                    $dateDeb      = ($row['dateDeb'] && $row['dateDeb'] !== '0000-00-00')
                        ? new \DateTime($row['dateDeb'])
                        : clone $debut;
                    $diffSemaines = (int)floor($dateDeb->diff($current)->days / 7);
                    if ($diffSemaines % $frequence !== 0) continue;
                }

                $hDebDt = \DateTime::createFromFormat('H:i', $hDeb);
                $hFinDt = \DateTime::createFromFormat('H:i', $hFin);
                if (!$hDebDt || !$hFinDt) continue;

                $datePresta = clone $current;

                // Doublon : même intervenant + même famille + même date + même type
                if ($this->horaireRepo->existsDoublonFamilleDate($numInter, $numFam, $datePresta, $typePresta)) {
                    $skipped++;
                    continue;
                }

                if (!$dryRun) {
                    $h = new Horaireinter();
                    $h->setNumInter($numInter);
                    $h->setNumFam($numFam);
                    $h->setNomFam($nomFam);     // label correct → getReleveData groupera par famille
                    $h->setTypePresta($typePresta);
                    $h->setDatePresta(clone $datePresta);
                    $h->setHeureDebutPresta($hDebDt);
                    $h->setHeureFinPresta($hFinDt);
                    $h->setAjouterLe(new \DateTime());
                    $h->setDesactiver(false);
                    $h->setDeclarerLeFam(null);
                    $h->setKmAvecEnfant(null);
                    $this->emHoraire->persist($h);
                    $batch++;

                    if ($batch % 100 === 0) {
                        $this->emHoraire->flush();
                    }
                }

                $created++;
            }

            $current->modify('+1 day');
        }

        if (!$dryRun && $batch > 0) {
            $this->emHoraire->flush();
        }

        $dryRun
            ? $io->note("[DRY-RUN] {$created} entrée(s) seraient créées, {$skipped} doublons.")
            : $io->success("{$created} heure(s) créée(s), {$skipped} ignorée(s) (doublons).");

        return Command::SUCCESS;
    }
}
