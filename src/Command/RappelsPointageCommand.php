<?php

namespace App\Command;

use App\Repository\HoraireinterRepository;
use App\Repository\IntervenantRepository;
use App\Repository\ProposerRepository;
use App\Service\EmailTemplateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Rappels de pointage par email, à lancer par cron / Task Scheduler (≈ toutes les 10 min) :
 *   - « fin proche »  : ~15 min avant la fin prévue au planning,
 *   - « oubli »       : ~20 min après la fin prévue et toujours non terminé.
 *
 * Anti-doublon : on cible des FENÊTRES de temps alignées sur la fréquence du cron
 * (option --fenetre). Lance la commande à la même fréquence que cette fenêtre.
 */
#[AsCommand(
    name: 'app:rappels-pointage',
    description: 'Envoie les rappels de pointage (fin proche / oubli) par email.',
)]
class RappelsPointageCommand extends Command
{
    private const FROM = 'noreplychaudoudoux@demomailtrap.co';

    public function __construct(
        private HoraireinterRepository $horaireRepo,
        private ProposerRepository     $proposerRepo,
        private IntervenantRepository  $intervenantRepo,
        private EmailTemplateService   $emailTemplates,
        private MailerInterface        $mailer,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les rappels sans envoyer d\'email.');
        $this->addOption('fenetre', null, InputOption::VALUE_OPTIONAL, 'Largeur de fenêtre en minutes (= fréquence du cron).', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);
        $dryRun  = (bool) $input->getOption('dry-run');
        $fenetre = max(1, (int) $input->getOption('fenetre'));

        $joursFr = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $jourAuj = $joursFr[(int) (new \DateTime())->format('w')];
        $now     = new \DateTime();
        $envoyes = 0;

        foreach ($this->horaireRepo->findEnCoursAujourdhuiTous() as $h) {
            $numInter = (int) $h->getNumInter();
            $numFam   = $h->getNumFam();
            $type     = strtoupper((string) $h->getTypePresta());

            if (!$numFam) {
                continue; // occasionnel sans famille → pas de fin prévue
            }

            // Heure de fin PRÉVUE = créneau proposer du jour (famille + jour + service).
            $finPrevue = null;
            foreach ($this->proposerRepo->findActivesByIntervenant($numInter) as $p) {
                if ((string) $p->getNumeroFamille() === (string) $numFam
                    && mb_strtolower(trim($p->getJour())) === $jourAuj
                    && strtoupper($p->getTypePrestation()) === $type
                    && $p->getHeureFin()) {
                    $finPrevue = $p->getHeureFin();
                    break;
                }
            }
            if (!$finPrevue) {
                continue; // pas de fin planifiée → on ne sait pas quand rappeler
            }

            // Minutes restantes avant la fin prévue (alignée sur aujourd'hui).
            $finToday = (clone $now)->setTime((int) $finPrevue->format('H'), (int) $finPrevue->format('i'), 0);
            $diffMin  = (int) round(($finToday->getTimestamp() - $now->getTimestamp()) / 60);

            // Choix du rappel selon la fenêtre.
            $cle = null;
            if ($diffMin <= 15 && $diffMin > 15 - $fenetre) {
                $cle = 'pointage_fin_proche';          // ~15 min avant
            } elseif (-$diffMin >= 20 && -$diffMin < 20 + $fenetre) {
                $cle = 'pointage_oubli';               // ~20 min après, toujours en cours
            }
            if (!$cle) {
                continue;
            }

            // Anti-doublon GARANTI : ce rappel a-t-il déjà été envoyé pour ce pointage ?
            if ($h->aDejaRappel($cle)) {
                continue;
            }

            $interv = $this->intervenantRepo->findInfosIntervenant($numInter);
            $email  = $interv?->getEmail();
            if (!$email) {
                continue;
            }

            $tpl = $this->emailTemplates->resoudre($cle, [
                'nom'     => $interv->getNomCompletInter(),
                'famille' => $h->getNomFam() ?: $numFam,
                'debut'   => $h->getHeureDebutPresta()?->format('H:i') ?? '—',
                'fin'     => $finPrevue->format('H:i'),
            ]);

            if ($dryRun) {
                $io->writeln(sprintf('[%s] → %s (%s, fin %s)', $cle, $email, $h->getNomFam() ?: $numFam, $finPrevue->format('H:i')));
                $envoyes++;
                continue;
            }

            try {
                $this->mailer->send(
                    (new Email())->from(self::FROM)->to($email)->subject($tpl['sujet'])->html($tpl['html'])
                );
                // Marque comme envoyé → ne repartira jamais une 2e fois.
                $h->ajouteRappel($cle);
                $this->em->flush();
                $envoyes++;
            } catch (\Throwable $e) {
                $io->warning(sprintf('Échec email %s : %s', $email, $e->getMessage()));
            }
        }

        $io->success(sprintf('%d rappel(s) %s.', $envoyes, $dryRun ? 'simulé(s)' : 'envoyé(s)'));
        return Command::SUCCESS;
    }
}
