<?php

namespace App\Service;

use App\Repository\UserSuiviRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MdpOublieService
{
    private const EMAIL_NOREPLY = 'noreplychaudoudoux@gmail.com';
    private const CODE_EXPIRATION = 3600; // 1 heure en secondes

    public function __construct(
        private UserSuiviRepository $userRepository,
        private MailerInterface $mailer
    ) {}

    /**
     * Génère un code temporaire et l'envoie par email
     */
    public function envoyerCodeReinitialisation(string $identifiant): bool
    {
        $user = $this->userRepository->findByIdentifiant($identifiant);
        
        if (!$user) {
            return false;
        }

        // Générer un code aléatoire de 6 chiffres
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Stocker le code en session (à adapter pour stocker en base de données)
        $_SESSION['reset_code'] = $code;
        $_SESSION['reset_code_time'] = time();
        $_SESSION['reset_identifiant'] = $identifiant;

        // Envoyer l'email
        return $this->envoyerEmail($identifiant, $code);
    }

    /**
     * Vérifie le code de réinitialisation
     */
    public function verifierCode(string $identifiant, string $code): bool
    {
        if (!isset($_SESSION['reset_code']) || 
            !isset($_SESSION['reset_code_time']) || 
            !isset($_SESSION['reset_identifiant'])) {
            return false;
        }

        // Vérifier l'identifiant
        if ($_SESSION['reset_identifiant'] !== $identifiant) {
            return false;
        }

        // Vérifier le code
        if ($_SESSION['reset_code'] !== $code) {
            return false;
        }

        // Vérifier l'expiration (1 heure)
        if (time() - $_SESSION['reset_code_time'] > self::CODE_EXPIRATION) {
            $this->nettoyerSession();
            return false;
        }

        return true;
    }

    /**
     * Réinitialise le mot de passe
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

        // Mettre à jour le mot de passe
        $user->setMdp(password_hash($nouveauMotDePasse, PASSWORD_DEFAULT));
        
        // Nettoyer la session
        $this->nettoyerSession();

        return true;
    }

    /**
     * Envoie l'email avec le code de réinitialisation
     */
    private function envoyerEmail(string $identifiant, string $code): bool
    {
        try {
            $email = (new Email())
                ->from(self::EMAIL_NOREPLY)
                ->to($this->getEmailDestinataire($identifiant))
                ->subject('Chaudoudoux - Réinitialisation de votre mot de passe')
                ->html($this->genererEmailContent($identifiant, $code));

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            // Loguer l'erreur
            error_log("Erreur envoi email réinitialisation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Génère le contenu de l'email
     */
    private function genererEmailContent(string $identifiant, string $code): string
    {
        return "
        <h2>Réinitialisation de votre mot de passe Chaudoudoux</h2>
        
        <p>Bonjour,</p>
        
        <p>Vous avez demandé la réinitialisation de votre mot de passe pour le compte :</p>
        <p><strong>{$identifiant}</strong></p>
        
        <p>Votre code de réinitialisation est :</p>
        <h3 style='background-color: #f0f0f0; padding: 10px; text-align: center; font-size: 24px; font-family: monospace;'>
        {$code}
        </h3>
        
        <p>Ce code est valable pendant 1 heure.</p>
        
        <p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.</p>
        
        <p>Cordialement,<br>L'équipe Chaudoudoux</p>
        ";
    }

    /**
     * Détermine l'email du destinataire
     * Note: Pour l'instant, les emails ne s'envoient pas aux familles et intervenants
     * (ligne 62 de mdpOublier.php mentionnée dans la doc)
     */
    private function getEmailDestinataire(string $identifiant): string
    {
        // Pour l'instant, on retourne une adresse par défaut
        // À adapter pour récupérer l'email réel de l'intervenant/famille
        
        // Si c'est l'admin
        if ($identifiant === '9.99.99.99.999.999.99') {
            return 'admin@chaudoudoux.fr';
        }

        // Pour les tests et développement
        return 'test@chaudoudoux.fr';
    }

    /**
     * Nettoie les variables de session de réinitialisation
     */
    private function nettoyerSession(): void
    {
        unset($_SESSION['reset_code']);
        unset($_SESSION['reset_code_time']);
        unset($_SESSION['reset_identifiant']);
    }
}
