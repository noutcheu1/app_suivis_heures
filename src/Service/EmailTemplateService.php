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
            'sujet'     => 'Votre code pour un nouveau mot de passe',
            'corps'     => "Bonjour,\n\n"
                . "Pas d'inquiétude, cela arrive à tout le monde !\n"
                . "Voici votre code pour choisir un nouveau mot de passe (compte {identifiant}) :\n\n"
                . "{code}\n\n"
                . "Ce code reste valable pendant 1 heure.\n"
                . "Si vous n'êtes pas à l'origine de cette demande, vous pouvez simplement ignorer ce message.\n\n"
                . "À très bientôt,\nL'équipe de La Maison des Chaudoudoux",
        ],
        'releve_signe' => [
            'label'     => "Relevé d'heures signé (avec PDF)",
            'variables' => [
                'libelle' => "Type de prestation (« Ménage » ou « Garde d'enfants »)",
            ],
            'sujet'     => "Votre relevé {libelle} est bien signé",
            'corps'     => "Bonjour,\n\n"
                . "Un grand merci pour votre travail !\n"
                . "Votre relevé d'heures {libelle} est bien signé, vous le trouverez en pièce jointe.\n\n"
                . "Belle journée,\nL'équipe de La Maison des Chaudoudoux",
        ],
        'rappel_releve' => [
            'label'     => 'Rappel : relevé à signer (à venir)',
            'variables' => [
                'nom'  => "Nom complet de l'intervenant",
                'mois' => 'Mois concerné (ex. « mai 2026 »)',
            ],
            'sujet'     => 'Votre relevé de {mois} vous attend',
            'corps'     => "Bonjour {nom},\n\n"
                . "Juste un petit mot amical : votre relevé d'heures du mois de {mois} est prêt "
                . "et n'attend plus que votre signature.\n"
                . "Cela ne vous prendra qu'un instant.\n\n"
                . "Belle journée,\nL'équipe de La Maison des Chaudoudoux",
        ],
        'pointage_fin_proche' => [
            'label'     => 'Rappel : fin de prestation proche (≈15 min avant)',
            'variables' => [
                'nom'     => "Nom complet de l'intervenant",
                'famille' => 'Nom de la famille',
                'debut'   => 'Heure de début (ex. « 08:00 »)',
                'fin'     => 'Heure de fin prévue (ex. « 16:00 »)',
            ],
            'sujet'     => 'Votre intervention se termine bientôt',
            'corps'     => "Bonjour {nom},\n\n"
                . "Votre intervention chez {famille} (commencée à {debut}) se termine bientôt, vers {fin}.\n"
                . "En partant, pensez simplement à terminer votre pointage pour que vos heures soient bien comptées.\n\n"
                . "Belle fin de journée,\nL'équipe de La Maison des Chaudoudoux",
        ],
        'pointage_oubli' => [
            'label'     => 'Rappel : pointage non terminé (oubli)',
            'variables' => [
                'nom'     => "Nom complet de l'intervenant",
                'famille' => 'Nom de la famille',
                'debut'   => 'Heure de début (ex. « 08:00 »)',
                'fin'     => 'Heure de fin prévue (ex. « 16:00 »)',
            ],
            'sujet'     => 'Un petit oubli de pointage ?',
            'corps'     => "Bonjour {nom},\n\n"
                . "Il semble que votre pointage chez {famille} (commencé à {debut}, fin prévue à {fin}) soit resté ouvert.\n"
                . "Pour que vos heures soient bien prises en compte, pensez à le terminer dès que possible.\n"
                . "Et en cas de doute, appelez-nous : nous le ferons ensemble.\n\n"
                . "À bientôt,\nL'équipe de La Maison des Chaudoudoux",
        ],
        'conges_famille' => [
            'label'     => 'Campagne de congés — email aux familles',
            'variables' => [
                'nom'     => 'Nom de la famille',
                'titre'   => 'Nom de la campagne (ex. « Vacances d\'été »)',
                'periode' => 'Période des vacances (ex. « du 15/07 au 31/08 »)',
                'limite'  => 'Date limite pour répondre (ex. « 30/06/2026 »)',
            ],
            'sujet'     => 'Préparons ensemble la période {titre}',
            'corps'     => "Famille {nom}, Bonjour\n\n"
                . "Les vacances approchent ! \n\n"
                . "Afin de préparer au mieux les plannings de la période "
                . "« {titre} » ({periode}), nous avons besoin de quelques informations.\n\n"
                . "Pourriez-vous nous indiquer votre période sans prestations Ménage relative a vos congés et nous "
                . "préciser si les prestations doivent être maintenues.\n\n"
                . "Vous pouvez compléter le formulaire avant le {limite} en cliquant sur le bouton ci-dessous.\n\n"
                . "Votre retour nous aidera à organiser au mieux les interventions.\n\n"
                . "L'équipe de La Maison des Chaudoudoux",
        ],
        'conges_intervenant' => [
            'label'     => 'Campagne de congés — email aux intervenants',
            'variables' => [
                'nom'     => "Nom complet de l'intervenant",
                'titre'   => 'Nom de la campagne (ex. « Vacances d\'été »)',
                'periode' => 'Période des vacances (ex. « du 15/07 au 31/08 »)',
                'limite'  => 'Date limite pour répondre (ex. « 30/06/2026 »)',
            ],
            'sujet'     => 'Vos disponibilités pour la période {titre}',
            'corps'     => "Bonjour {nom},\n\n"
                . "Les vacances approchent ! \n\n" 
                . " Afin de préparer au mieux les plannings de la période "
                . "« {titre} » ({periode}), nous vous invitons à nous communiquer vos disponibilités.\n\n"
                . "Pourriez-vous nous indiquer vos dates de congés, {titre} "
                . "Et pouriez vous  nous préciser si vous ètes "
                . "disponible pour assurer quelques remplacements en dehord de votre période de congés ?\n\n"
                . "Quelques minutes suffisent : il vous suffit de compléter le formulaire avant le {limite} "
                . "en cliquant sur le bouton ci-dessous.\n\n"
                . "Votre retour nous est précieux pour organiser au mieux les interventions.\n\n"
                . "L'équipe de La Maison des Chaudoudoux",
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
    /** Catégorie d'affichage de chaque modèle (pour regrouper à l'écran). */
    private const CATEGORIES = [
        'mdp_oublie'          => 'Connexion & compte',
        'releve_signe'        => 'Relevés',
        'rappel_releve'       => 'Relevés',
        'pointage_fin_proche' => 'Pointage',
        'pointage_oubli'      => 'Pointage',
        'conges_famille'      => 'Congés',
        'conges_intervenant'  => 'Congés',
    ];

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
                'categorie'    => self::CATEGORIES[$cle] ?? 'Autres',
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
