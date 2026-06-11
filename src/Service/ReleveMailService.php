<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Signe un relevé d'heures et l'envoie par email (PDF en pièce jointe, généré
 * côté navigateur) à l'intervenant, avec la structure Chaudoudoux en copie.
 */
class ReleveMailService
{
    // Adresse expéditrice et copie (structure Chaudoudoux).
    private const EMAIL_NOREPLY   = 'noreplychaudoudoux@demomailtrap.co';
    private const EMAIL_STRUCTURE = 'noreplychaudoudoux@demomailtrap.co';

    public function __construct(
        private MailerInterface     $mailer,
        private HoraireinterService $horaireService,
    ) {}

    /**
     * @return array{success: bool, emailEnvoye?: bool, message?: string}
     */
    public function signerEtEnvoyer(
        int $numInter,
        string $type,
        string $periodeFin,
        ?string $emailDest,
        ?string $pdfBase64,
        string $filename = 'releve.pdf',
    ): array {
        // 1) Signer (réutilise la logique existante et ses contrôles de période)
        $result = $this->horaireService->signerReleve($numInter, $type, $periodeFin);
        if (!$result['success']) {
            return $result;
        }

        // 2) Envoyer le relevé par email (si destinataire + PDF fournis)
        if (!$emailDest || !$pdfBase64) {
            return ['success' => true, 'emailEnvoye' => false];
        }

        try {
            $pdfContent = $this->decoderPdf($pdfBase64);
            if ($pdfContent === null) {
                return ['success' => true, 'emailEnvoye' => false, 'message' => 'PDF invalide'];
            }

            $libelle = strtoupper($type) === 'MENA' ? 'Ménage' : "Garde d'enfants";
            $email = (new Email())
                ->from(self::EMAIL_NOREPLY)
                ->to($emailDest)
                ->cc(self::EMAIL_STRUCTURE)
                ->subject("Chaudoudoux — Relevé d'heures signé ({$libelle})")
                ->html(
                    "<p>Bonjour,</p>"
                    . "<p>Votre relevé d'heures <strong>{$libelle}</strong> a bien été signé. "
                    . "Vous le trouverez en pièce jointe.</p>"
                    . "<p style='font-size:12px;color:#94a3b8;'>— La Maison des Chaudoudoux</p>"
                )
                ->attach($pdfContent, $filename, 'application/pdf');

            $this->mailer->send($email);

            return ['success' => true, 'emailEnvoye' => true];
        } catch (\Throwable $e) {
            // La signature reste valide même si l'email échoue.
            return [
                'success'     => true,
                'emailEnvoye' => false,
                'message'     => "Relevé signé, mais l'envoi de l'email a échoué : " . $e->getMessage(),
            ];
        }
    }

    /** Décode un PDF en data URI ("data:application/pdf;base64,XXXX") ou base64 brut. */
    private function decoderPdf(string $pdfBase64): ?string
    {
        $base64 = $pdfBase64;
        if (str_contains($base64, ',')) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }
        $content = base64_decode($base64, true);
        return $content === false ? null : $content;
    }
}
