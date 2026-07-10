<?php

namespace App\Service;

use App\Repository\FamilleRepository;
use App\Repository\IntervenantRepository;
use App\Repository\UserSuiviRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MdpOublieService
{
    private const EMAIL_NOREPLY    = 'noreplychaudoudoux@gmail.com';
    private const CODE_EXPIRATION  = 3600; // 1 heure
    private const MAX_PAR_JOUR     = 3;    // demandes de réinitialisation / jour / personne

    public const ENVOI_OK          = 'ok';
    public const ENVOI_INTROUVABLE = 'introuvable';
    public const ENVOI_LIMITE      = 'limite';

    public function __construct(
        private UserSuiviRepository  $userRepository,
        private EntityManagerInterface $em,
        private MailerInterface      $mailer,
        private IntervenantRepository $intervenantRepository,
        private FamilleRepository    $familleRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailTemplateService $emailTemplates,
        private AuditLogger $audit,
    ) {}

    /**
     * Génère un code à 6 chiffres, le persiste et l'envoie par email.
     * Limité à MAX_PAR_JOUR demandes par jour et par personne (anti-spam).
     *
     * @return string self::ENVOI_OK | ENVOI_INTROUVABLE | ENVOI_LIMITE
     */
    public function envoyerCodeReinitialisation(string $identifiant): string
    {
        // L'intervenant saisit son TÉLÉPHONE → on le résout en identifiant de compte
        // (numSalarie). Famille/admin : leur identifiant est utilisé tel quel.
        $identifiant = $this->resoudreUsername($identifiant);

        $user = $this->userRepository->findByIdentifiant($identifiant);
        if (!$user) {
            return self::ENVOI_INTROUVABLE;
        }

        $email = $this->trouverEmail($identifiant);
        if (!$email) {
            return self::ENVOI_INTROUVABLE;
        }

        // Limite : 3 demandes / jour / personne. Le compteur repart à 0 chaque jour.
        $aujourdhui = new \DateTime('today');
        $memeJour   = $user->getReinitCompteurLe() instanceof \DateTimeInterface
            && $user->getReinitCompteurLe()->format('Y-m-d') === $aujourdhui->format('Y-m-d');
        if ($memeJour && $user->getReinitCompteur() >= self::MAX_PAR_JOUR) {
            $this->audit->log('mdp_reinit_limite', ['compte' => $identifiant]);
            return self::ENVOI_LIMITE;
        }

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->setTokenReinit($code);
        $user->setTokenExpire(new \DateTime('+1 hour'));
        $user->setReinitCompteur($memeJour ? $user->getReinitCompteur() + 1 : 1);
        $user->setReinitCompteurLe($aujourdhui);
        $this->em->flush();

        return $this->envoyerEmail($email, $identifiant, $code) ? self::ENVOI_OK : self::ENVOI_INTROUVABLE;
    }

    /**
     * Vérifie que le code correspond et n'est pas expiré.
     */
    public function verifierCode(string $identifiant, string $code): bool
    {
        $identifiant = $this->resoudreUsername($identifiant);

        $user = $this->userRepository->findByIdentifiant($identifiant);
        if (!$user) {
            return false;
        }

        if ($user->getTokenReinit() === null || $user->getTokenExpire() === null) {
            return false;
        }

        if ($user->getTokenExpire() < new \DateTime()) {
            return false;
        }

        return $user->getTokenReinit() === $code;
    }

    /**
     * Réinitialise le mot de passe après vérification du code.
     */
    public function reinitialiserMotDePasse(string $identifiant, string $code, string $nouveauMotDePasse): bool
    {
        if (!$this->verifierCode($identifiant, $code)) {
            return false;
        }

        $identifiant = $this->resoudreUsername($identifiant);
        $user = $this->userRepository->findByIdentifiant($identifiant);
        if (!$user) {
            return false;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $nouveauMotDePasse));
        $user->setTokenReinit(null);
        $user->setTokenExpire(null);
        $this->em->flush();

        // AUDIT : action sensible — réinitialisation du mot de passe.
        $this->audit->log('password_change', [
            'actor'  => $user->getUserIdentifier(),
            'method' => 'reinitialisation',
        ]);

        return true;
    }

    /**
     * Résout la valeur SAISIE en identifiant de compte (users_suivi.username) :
     *  - si elle correspond déjà à un compte (famille, admin, numSalarie) → telle quelle ;
     *  - sinon, l'intervenant a saisi son TÉLÉPHONE → on le résout en numSalarie.
     * Retourne la valeur d'origine si rien ne correspond (l'appelant échoue proprement).
     */
    private function resoudreUsername(string $saisi): string
    {
        if ($this->userRepository->findByIdentifiant($saisi)) {
            return $saisi;
        }
        $intervenant = $this->intervenantRepository->findByTelephoneNormalise($saisi);
        return $intervenant?->getNumSalarie() ?? $saisi;
    }

    /**
     * Cherche l'email réel de l'utilisateur dans les tables intervenant/famille.
     */
    private function trouverEmail(string $identifiant): ?string
    {
        $intervenant = $this->intervenantRepository->findByNumSalarie($identifiant)
            ?? $this->intervenantRepository->findByNumSs($identifiant);

        if ($intervenant?->getEmail()) {
            return $intervenant->getEmail();
        }

        $famille = $this->familleRepository->findByNumero($identifiant);
        if ($famille?->getEmail()) {
            return $famille->getEmail();
        }

        // Admin
        if ($identifiant === '9999999999') {
            return 'admin@chaudoudoux.fr';
        }

        return null;
    }

    private function envoyerEmail(string $destinataire, string $identifiant, string $code): bool
    {
        try {
            // Modèle personnalisable par l'admin (repli sur le texte par défaut).
            $tpl = $this->emailTemplates->resoudre('mdp_oublie', [
                'identifiant' => $identifiant,
                'code'        => $code,
            ]);

            $email = (new Email())
                ->from(self::EMAIL_NOREPLY)
                ->to($destinataire)
                ->subject($tpl['sujet'])
                ->html($tpl['html']);

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            error_log('Erreur envoi email reset: ' . $e->getMessage());
            return false;
        }
    }
}
