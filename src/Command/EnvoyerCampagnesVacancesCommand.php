<?php

namespace App\Command;

use App\Entity\Horaire\VacancesConfig;
use App\Entity\Horaire\VacancesReponseFamille;
use App\Entity\Horaire\VacancesReponseIntervenant;
use App\Repository\VacancesConfigRepository;
use App\Repository\VacancesReponseFamilleRepository;
use App\Repository\VacancesReponseIntervenantRepository;
use App\Service\EmailTemplateService;
use App\Service\FamilleIntervenantService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Envoi AUTOMATIQUE des campagnes de congés dont la date d'ouverture est atteinte.
 * À lancer chaque jour par cron / Task Scheduler.
 *
 * Pour chaque campagne « brouillon » ouverte : crée une réponse (avec jeton) par
 * famille et intervenant PREST, envoie l'email avec le lien, puis passe la campagne
 * en « envoyée » (donc relancer la commande ne renvoie pas les emails).
 */
#[AsCommand(
    name: 'app:envoyer-campagnes-vacances',
    description: 'Envoie les emails des campagnes de congés dont la date d\'ouverture est atteinte.',
)]
class EnvoyerCampagnesVacancesCommand extends Command
{
    private const FROM = 'noreplychaudoudoux@demomailtrap.co';

    public function __construct(
        private VacancesConfigRepository             $configRepo,
        private VacancesReponseFamilleRepository     $reponseFamRepo,
        private VacancesReponseIntervenantRepository $reponseInterRepo,
        private FamilleIntervenantService            $familleIntervenantService,
        private EmailTemplateService                 $emailTemplates,
        private MailerInterface                      $mailer,
        private EntityManagerInterface               $em,
        private UrlGeneratorInterface                $router,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche sans envoyer ni enregistrer.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $today  = new \DateTimeImmutable('today');

        // Interrupteur global des emails planifiés (désactivé par défaut).
        // Passer EMAILS_PLANIFIES_ACTIFS=1 dans .env pour réactiver l'envoi.
        if (!$dryRun && !filter_var($_ENV['EMAILS_PLANIFIES_ACTIFS'] ?? getenv('EMAILS_PLANIFIES_ACTIFS'), FILTER_VALIDATE_BOOLEAN)) {
            $io->note('Emails planifiés désactivés (EMAILS_PLANIFIES_ACTIFS non actif). Aucune campagne envoyée.');
            return Command::SUCCESS;
        }

        $campagnes = $this->configRepo->findAEnvoyer($today);
        if (!$campagnes) {
            $io->success('Aucune campagne à envoyer aujourd\'hui.');
            return Command::SUCCESS;
        }

        foreach ($campagnes as $config) {
            $io->section($config->getTitre());
            $periode = $this->periode($config);
            $limite  = $config->getDateLimiteReponse() ? $this->dateFr($config->getDateLimiteReponse()) : '—';
            $nb      = 0;

            // ── Familles PREST ──
            foreach ($this->familleIntervenantService->getFamillesActives() as $fam) {
                $numFam = (string) $fam->getNumeroFamille();
                if ($numFam === '' || $this->reponseFamRepo->existsPour((int) $config->getId(), $numFam)) {
                    continue;
                }
                $rep = (new VacancesReponseFamille())
                    ->setVacancesConfigId((int) $config->getId())
                    ->setNumFam($numFam);

                $email = $fam->getEmail();
                if ($email && !$dryRun) {
                    $lien = $this->router->generate('vacances_reponse_famille', ['token' => $rep->getToken()], UrlGeneratorInterface::ABSOLUTE_URL);
                    $tpl  = $this->emailTemplates->resoudre('conges_famille', [
                        'nom' => $fam->getNomFamille() ?: $numFam, 'titre' => $config->getTitre(),
                        'periode' => $periode, 'limite' => $limite,
                    ]);
                    $this->envoyer($email, $tpl, $lien, $io);
                }
                if (!$dryRun) {
                    $this->em->persist($rep);
                }
                $nb++;
            }

            // ── Intervenants PREST ──
            foreach ($this->familleIntervenantService->getIntervenantsActifs() as $inter) {
                $numInter = (int) $inter->getId();
                if ($numInter <= 0 || $this->reponseInterRepo->existsPour((int) $config->getId(), $numInter)) {
                    continue;
                }
                $rep = (new VacancesReponseIntervenant())
                    ->setVacancesConfigId((int) $config->getId())
                    ->setNumInter($numInter);

                $email = $inter->getEmail();
                if ($email && !$dryRun) {
                    $lien = $this->router->generate('vacances_reponse_intervenant', ['token' => $rep->getToken()], UrlGeneratorInterface::ABSOLUTE_URL);
                    $tpl  = $this->emailTemplates->resoudre('conges_intervenant', [
                        'nom' => $inter->getNomCompletInter(), 'titre' => $config->getTitre(),
                        'periode' => $periode, 'limite' => $limite,
                    ]);
                    $this->envoyer($email, $tpl, $lien, $io);
                }
                if (!$dryRun) {
                    $this->em->persist($rep);
                }
                $nb++;
            }

            if (!$dryRun) {
                $config->setStatut(VacancesConfig::STATUT_ENVOYEE);
                $this->em->flush();
            }
            $io->writeln(sprintf('  → %d destinataire(s) %s.', $nb, $dryRun ? 'simulé(s)' : 'traité(s)'));
        }

        $io->success(sprintf('%d campagne(s) %s.', count($campagnes), $dryRun ? 'simulée(s)' : 'envoyée(s)'));
        return Command::SUCCESS;
    }

    private function periode(VacancesConfig $c): string
    {
        if ($c->getDateDebut() && $c->getDateFin()) {
            return 'du ' . $this->dateFr($c->getDateDebut()) . ' au ' . $this->dateFr($c->getDateFin());
        }
        return $c->getTitre();
    }

    /** Formate une date en toutes lettres en français : « 24 septembre 2026 ». */
    private function dateFr(\DateTimeInterface $d): string
    {
        $mois = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        return (int) $d->format('j') . ' ' . $mois[(int) $d->format('n')] . ' ' . $d->format('Y');
    }

    /** @param array{sujet:string, html:string} $tpl */
    private function envoyer(string $dest, array $tpl, string $lien, SymfonyStyle $io): void
    {
        // Bouton cliquable ajouté après le message brandé.
        $bouton = '<div style="text-align:center;margin:22px 0;">'
            . '<a href="' . htmlspecialchars($lien, ENT_QUOTES) . '" '
            . 'style="background:#4f46e5;color:#ffffff;text-decoration:none;padding:13px 26px;border-radius:8px;font-weight:600;display:inline-block;">'
            . 'Remplir le formulaire</a></div>';
        try {
            $this->mailer->send(
                (new Email())->from(self::FROM)->to($dest)->subject($tpl['sujet'])->html($tpl['html'] . $bouton)
            );
        } catch (\Throwable $e) {
            $io->warning(sprintf('Échec email %s : %s', $dest, $e->getMessage()));
        }
    }
}
