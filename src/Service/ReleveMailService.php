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
    private const EMAIL_NOREPLY   = 'mchaudoudoux@aol.com';
    private const EMAIL_STRUCTURE = 'mchaudoudoux@aol.com';

    public function __construct(
        private MailerInterface     $mailer,
        private HoraireinterService $horaireService,
        private EmailTemplateService $emailTemplates,
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
            // Modèle personnalisable par l'admin (repli sur le texte par défaut).
            $tpl = $this->emailTemplates->resoudre('releve_signe', ['libelle' => $libelle]);

            $email = (new Email())
                ->from(self::EMAIL_NOREPLY)
                ->to($emailDest)
                ->cc(self::EMAIL_STRUCTURE)
                ->subject($tpl['sujet'])
                ->html($tpl['html'])
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
