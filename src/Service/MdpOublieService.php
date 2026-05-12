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

    public function __construct(
        private UserSuiviRepository  $userRepository,
        private EntityManagerInterface $em,
        private MailerInterface      $mailer,
        private IntervenantRepository $intervenantRepository,
        private FamilleRepository    $familleRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    /**
     * Génère un code à 6 chiffres, le persiste en DB et l'envoie par email.
     */
    public function envoyerCodeReinitialisation(string $identifiant): bool
    {
        $user = $this->userRepository->findByIdentifiant($identifiant);
        if (!$user) {
            return false;
        }

        $email = $this->trouverEmail($identifiant);
        if (!$email) {
            return false;
        }

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->setTokenReinit($code);
        $user->setTokenExpire(new \DateTime('+1 hour'));
        $this->em->flush();

        return $this->envoyerEmail($email, $identifiant, $code);
    }

    /**
     * Vérifie que le code correspond et n'est pas expiré.
     */
    public function verifierCode(string $identifiant, string $code): bool
    {
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

        $user = $this->userRepository->findByIdentifiant($identifiant);
        if (!$user) {
            return false;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $nouveauMotDePasse));
        $user->setTokenReinit(null);
        $user->setTokenExpire(null);
        $this->em->flush();

        return true;
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
        if ($identifiant === '9.99.99.99.999.999.99') {
            return 'admin@chaudoudoux.fr';
        }

        return null;
    }

    private function envoyerEmail(string $destinataire, string $identifiant, string $code): bool
    {
        try {
            $html = "
            <div style='font-family:Inter,sans-serif;max-width:480px;margin:0 auto;'>
                <h2 style='color:#1e1b4b;'>Réinitialisation de votre mot de passe</h2>
                <p>Bonjour,</p>
                <p>Vous avez demandé la réinitialisation du mot de passe pour le compte&nbsp;:
                   <strong>{$identifiant}</strong></p>
                <p>Votre code de réinitialisation&nbsp;:</p>
                <div style='background:#f1f5f9;border-radius:10px;padding:16px;text-align:center;
                            font-size:32px;font-weight:700;letter-spacing:8px;
                            color:#4f46e5;font-family:monospace;margin:16px 0;'>
                    {$code}
                </div>
                <p style='font-size:13px;color:#64748b;'>
                    Ce code est valable <strong>1 heure</strong>.<br>
                    Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.
                </p>
                <p style='font-size:12px;color:#94a3b8;'>— L'équipe La Maison des Chaudoudoux</p>
            </div>";

            $email = (new Email())
                ->from(self::EMAIL_NOREPLY)
                ->to($destinataire)
                ->subject('Chaudoudoux — Code de réinitialisation')
                ->html($html);

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            error_log('Erreur envoi email reset: ' . $e->getMessage());
            return false;
        }
    }
}
