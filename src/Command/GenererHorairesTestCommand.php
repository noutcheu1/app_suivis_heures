<?php

namespace App\Command;

use App\Entity\Horaire\Horaireinter;
use App\Repository\HoraireinterRepository;
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
        private ManagerRegistry        $doctrine,
        private \App\Twig\FamilleExtension $familleExtension,
        private \App\Repository\ProposerRepository $proposerRepo,
        private \App\Service\FamilleIntervenantService $familleIntervenantService,
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

        // Intervenants actifs (service) puis expansion du planning via le service
        // expandForPeriodIntervenant() qui gère déjà jour + fréquence bi-hebdo.
        $intervenants = $this->familleIntervenantService->getIntervenantsActifs();
        $io->text(count($intervenants) . ' intervenant(s) actif(s)');

        $created = 0;
        $skipped = 0;
        $batch   = 0;

        foreach ($intervenants as $inter) {
            $numInter = (int) $inter->getId();
            $occurrences = $this->proposerRepo->expandForPeriodIntervenant($numInter, clone $debut, clone $fin);

            foreach ($occurrences as ['date' => $datePresta, 'proposer' => $p]) {
                $numFam = (string) $p->getNumeroFamille();
                // Nom via le SERVICE famille_label() → identique aux relevés
                $nomFam = $this->familleExtension->familleLabel($numFam) ?: $numFam;

                $typeRaw    = strtoupper((string) $p->getTypePrestation());
                $typePresta = (str_contains($typeRaw, 'MENA') || $typeRaw === 'M') ? 'MENA' : 'ENFA';

                $hDebDt = $p->getHeureDebut();
                $hFinDt = $p->getHeureFin();
                if (!$hDebDt || !$hFinDt) {
                    continue;
                }

                // Doublon : même intervenant + même famille + même date + même type
                if ($this->horaireRepo->existsDoublonFamilleDate($numInter, $numFam, $datePresta, $typePresta)) {
                    $skipped++;
                    continue;
                }

                if (!$dryRun) {
                    $h = new Horaireinter();
                    $h->setNumInter($numInter);
                    $h->setNumFam($numFam);
                    $h->setNomFam($nomFam);
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
