<?php

namespace App\Command;

use App\Repository\HoraireinterRepository;
use App\Service\HoraireinterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculer-distances',
    description: 'Remplit kmTrajet (distance intervenant → famille) sur les prestations existantes.',
)]
class CalculerDistancesCommand extends Command
{
    public function __construct(
        private HoraireinterRepository $horaireRepo,
        private HoraireinterService    $horaireService,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('all', null, InputOption::VALUE_NONE, 'Recalcule TOUTES les prestations (pas seulement kmTrajet NULL)');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simuler sans enregistrer');
        $this->addOption('sleep', null, InputOption::VALUE_OPTIONAL, 'Pause (ms) entre familles pour ménager les API', 1100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);
        $all     = (bool) $input->getOption('all');
        $dryRun  = (bool) $input->getOption('dry-run');
        $sleepMs = (int) $input->getOption('sleep');

        // Prestations actives avec une vraie famille (occasionnelle exclue)
        $qb = $this->horaireRepo->createQueryBuilder('h')
            ->where('h.desactiver = false')
            ->andWhere('h.numFam IS NOT NULL')
            ->andWhere("h.numFam != '0'");
        if (!$all) {
            $qb->andWhere('h.kmTrajet IS NULL');
        }
        $prestations = $qb->getQuery()->getResult();

        $total = count($prestations);
        if ($total === 0) {
            $io->success('Aucune prestation à traiter.');
            return Command::SUCCESS;
        }

        $io->title(sprintf('Calcul des distances %d prestation(s)%s', $total, $dryRun ? ' (DRY-RUN)' : ''));

        // Cache des distances déjà calculées par couple (intervenant|famille)
        // → on n'appelle les API qu'une fois par couple, même sur N prestations.
        $cache   = [];
        $okCount = 0;
        $nullCount = 0;
        $io->progressStart($total);

        foreach ($prestations as $h) {
            $numInter = (int) $h->getNumInter();
            $numFam   = (string) $h->getNumFam();
            $key      = $numInter . '|' . $numFam;

            if (!array_key_exists($key, $cache)) {
                $cache[$key] = $this->horaireService->calculerKmTrajet($numInter, $numFam);
                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000); // respecter la limite Nominatim (~1 req/s)
                }
            }
            $km = $cache[$key];

            if ($km !== null) {
                if (!$dryRun) {
                    $h->setKmTrajet((string) $km);
                }
                $okCount++;
            } else {
                $nullCount++;
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        if (!$dryRun) {
            $this->em->flush();
        }

        $io->success(sprintf(
            '%d distance(s) calculée(s), %d échec(s) (géocodage/API). %s',
            $okCount,
            $nullCount,
            $dryRun ? 'Aucune écriture (dry-run).' : 'Enregistré.'
        ));

        if ($nullCount > 0) {
            $io->note('Les échecs (kmTrajet resté NULL) peuvent être relancés plus tard : adresse incomplète ou API indisponible.');
        }

        return Command::SUCCESS;
    }
}
