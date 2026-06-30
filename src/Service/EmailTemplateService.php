<?php

namespace App\Service;

use App\Entity\Horaire\EmailTemplate;
use App\Repository\EmailTemplateRepository;

/**
 * Gère les modèles d'email personnalisables par l'admin.
 *
 * Chaque modèle a une clé stable, un sujet et un corps (texte avec variables
 * {nom}). Si l'admin n'a rien personnalisé (ou si la table n'existe pas encore),
 * on retombe automatiquement sur les textes par défaut → aucun email cassé.
 */
class EmailTemplateService
{
    /**
     * Catalogue des modèles connus : clé → label, variables disponibles,
     * sujet et corps par défaut. Sert aussi à construire l'écran d'admin.
     */
    private const CATALOGUE = [
        'mdp_oublie' => [
            'label'     => 'Code de mot de passe oublié',
            'variables' => [
                'identifiant' => 'Identifiant du compte (n° salarié intervenant ou n° famille)',
                'code'        => 'Code de réinitialisation à 6 chiffres',
            ],
            'sujet'     => 'Chaudoudoux Code de réinitialisation',
            'corps'     => "Bonjour,\n\n"
                . "Vous avez demandé la réinitialisation du mot de passe pour le compte : {identifiant}\n\n"
                . "Votre code de réinitialisation : {code}\n\n"
                . "Ce code est valable 1 heure.\n"
                . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n\n"
                . "L'équipe La Maison des Chaudoudoux",
        ],
        'releve_signe' => [
            'label'     => "Relevé d'heures signé (avec PDF)",
            'variables' => [
                'libelle' => "Type de prestation (« Ménage » ou « Garde d'enfants »)",
            ],
            'sujet'     => "Chaudoudoux Relevé d'heures signé ({libelle})",
            'corps'     => "Bonjour,\n\n"
                . "Votre relevé d'heures {libelle} a bien été signé. Vous le trouverez en pièce jointe.\n\n"
                . "La Maison des Chaudoudoux",
        ],
        'rappel_releve' => [
            'label'     => 'Rappel : relevé à signer (à venir)',
            'variables' => [
                'nom'  => "Nom complet de l'intervenant",
                'mois' => 'Mois concerné (ex. « mai 2026 »)',
            ],
            'sujet'     => 'Chaudoudoux Pensez à signer votre relevé de {mois}',
            'corps'     => "Bonjour {nom},\n\n"
                . "Votre relevé d'heures du mois de {mois} est prêt et attend votre signature.\n\n"
                . "La Maison des Chaudoudoux",
        ],
        'pointage_fin_proche' => [
            'label'     => 'Rappel : fin de prestation proche (≈15 min avant)',
            'variables' => [
                'nom'     => "Nom complet de l'intervenant",
                'famille' => 'Nom de la famille',
                'debut'   => 'Heure de début (ex. « 08:00 »)',
                'fin'     => 'Heure de fin prévue (ex. « 16:00 »)',
            ],
            'sujet'     => 'Chaudoudoux Votre prestation se termine bientôt',
            'corps'     => "Bonjour {nom},\n\n"
                . "Votre prestation chez {famille} (commencée à {debut}) est prévue jusqu'à {fin}.\n"
                . "Pensez à terminer votre pointage à la fin de l'intervention.\n\n"
                . "La Maison des Chaudoudoux",
        ],
        'pointage_oubli' => [
            'label'     => 'Rappel : pointage non terminé (oubli)',
            'variables' => [
                'nom'     => "Nom complet de l'intervenant",
                'famille' => 'Nom de la famille',
                'debut'   => 'Heure de début (ex. « 08:00 »)',
                'fin'     => 'Heure de fin prévue (ex. « 16:00 »)',
            ],
            'sujet'     => 'Chaudoudoux N\'oubliez pas de terminer votre pointage',
            'corps'     => "Bonjour {nom},\n\n"
                . "Votre pointage chez {famille} (commencé à {debut}, fin prévue à {fin}) est toujours en cours.\n"
                . "Merci de le terminer dès que possible pour enregistrer vos heures.\n\n"
                . "La Maison des Chaudoudoux",
        ],
    ];

    public function __construct(
        private EmailTemplateRepository $repository,
    ) {}

    /**
     * Résout un email : retourne le sujet et le corps HTML prêts à envoyer,
     * variables remplacées. Repli sur les valeurs par défaut si non personnalisé.
     *
     * @param array<string,string> $vars
     * @return array{sujet:string, html:string}
     */
    public function resoudre(string $cle, array $vars = []): array
    {
        $defaut = self::CATALOGUE[$cle] ?? ['sujet' => '', 'corps' => ''];

        $sujet = $defaut['sujet'];
        $corps = $defaut['corps'];

        // Personnalisation admin si disponible (et table présente).
        try {
            $tpl = $this->repository->find($cle);
            if ($tpl instanceof EmailTemplate) {
                if (trim($tpl->getSujet()) !== '') { $sujet = $tpl->getSujet(); }
                if (trim($tpl->getCorps()) !== '') { $corps = $tpl->getCorps(); }
            }
        } catch (\Throwable) {
            // Table absente / DB indisponible → on garde les défauts.
        }

        $sujet = $this->remplacer($sujet, $vars);
        $corps = $this->remplacer($corps, $vars);

        return ['sujet' => $sujet, 'html' => $this->enrobageHtml($corps)];
    }

    /**
     * Liste des modèles pour l'écran d'admin : clé, label, variables, et les
     * valeurs actuelles (personnalisées ou par défaut).
     *
     * @return list<array{cle:string, label:string, variables:string[], sujet:string, corps:string, personnalise:bool}>
     */
    public function listerPourAdmin(): array
    {
        $out = [];
        foreach (self::CATALOGUE as $cle => $def) {
            $sujet = $def['sujet'];
            $corps = $def['corps'];
            $perso = false;
            try {
                $tpl = $this->repository->find($cle);
                if ($tpl instanceof EmailTemplate) {
                    $sujet = $tpl->getSujet() !== '' ? $tpl->getSujet() : $sujet;
                    $corps = $tpl->getCorps() !== '' ? $tpl->getCorps() : $corps;
                    $perso = true;
                }
            } catch (\Throwable) {
                // table absente → valeurs par défaut
            }
            $out[] = [
                'cle'          => $cle,
                'label'        => $def['label'],
                'variables'    => $def['variables'],
                'sujet'        => $sujet,
                'corps'        => $corps,
                'personnalise' => $perso,
            ];
        }
        return $out;
    }

    public function sauvegarder(string $cle, string $sujet, string $corps): void
    {
        if (!isset(self::CATALOGUE[$cle])) {
            return; // clé inconnue : on ignore
        }
        $tpl = $this->repository->find($cle) ?? new EmailTemplate($cle);
        $tpl->setSujet($sujet)->setCorps($corps)->touch();
        $this->repository->save($tpl);
    }

    public function reinitialiser(string $cle): void
    {
        $def = self::CATALOGUE[$cle] ?? null;
        if ($def === null) {
            return;
        }
        $this->sauvegarder($cle, $def['sujet'], $def['corps']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** @param array<string,string> $vars */
    private function remplacer(string $texte, array $vars): string
    {
        foreach ($vars as $nom => $valeur) {
            $texte = str_replace('{' . $nom . '}', (string) $valeur, $texte);
        }
        return $texte;
    }

    /**
     * Enrobe le corps (texte saisi par l'admin) dans un email HTML brandé
     * (en-tête + pied de page), avec styles inline compatibles clients mail.
     */
    private function enrobageHtml(string $corps): string
    {
        $html = nl2br(htmlspecialchars($corps, ENT_QUOTES, 'UTF-8'));

        return <<<HTML
        <div style="background:#f1f5f9;padding:24px 12px;font-family:Inter,Arial,sans-serif;">
          <table role="presentation" align="center" cellpadding="0" cellspacing="0" width="100%" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.06);">
            <tr>
              <td style="background:#4f46e5;padding:20px 28px;">
                <span style="color:#ffffff;font-size:18px;font-weight:700;">La Maison des Chaudoudoux</span>
              </td>
            </tr>
            <tr>
              <td style="padding:28px;color:#0f172a;font-size:15.5px;line-height:1.65;">
                {$html}
              </td>
            </tr>
            <tr>
              <td style="padding:16px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;">
                Cet email est envoyé automatiquement, merci de ne pas y répondre.
              </td>
            </tr>
          </table>
        </div>
        HTML;
    }
}
