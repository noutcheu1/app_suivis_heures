<?php

namespace App\Controller;

use App\Repository\IntervenantRepository;
use App\Service\AuthService;
use App\Service\FamilleIntervenantService;
use App\Service\FamilleService;
use App\Service\HoraireinterService;
use App\Twig\FamilleExtension;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pointage QR SANS connexion.
 *
 * L'intervenant scanne le QR de la famille (qui pointe vers /declarer/{numFam}).
 * S'il n'est pas connecté, il est redirigé ici : il saisit son numéro de téléphone,
 * confirme son nom, puis démarre / termine son pointage. Aucune session
 * authentifiée n'est créée on ré-identifie l'intervenant par son numéro à chaque
 * appel (jamais de numInter fourni par le client n'est utilisé tel quel).
 */
final class PointageController extends AbstractController
{
    private const MAX_TENTATIVES   = 10;     // par fenêtre
    private const FENETRE_SECONDES = 600;    // 10 min

    public function __construct(
        private IntervenantRepository     $intervenantRepository,
        private FamilleService            $familleService,
        private FamilleIntervenantService $familleIntervenantService,
        private HoraireinterService       $horaireService,
        private FamilleExtension          $familleExtension,
        private AuthService               $authService,
        private LoggerInterface           $logger,
    ) {}

    #[Route('/pointage/{numFam}', name: 'pointage_saisie', methods: ['GET'])]
    public function saisie(string $numFam, Request $request): Response
    {
        $famille = $this->familleService->getFamilleParNumero($numFam);
        $nomFam  = $this->familleExtension->familleLabel($famille ?? $numFam) ?: $numFam;

        // Si un intervenant est CONNECTÉ : on récupère son numéro de téléphone pour
        // sauter l'étape de saisie (même flux ensuite). Sinon, champ vide → il le saisit.
        $telPrefill = '';
        if ($this->authService->check() && ($iid = $this->authService->intervenant_id())) {
            $telPrefill = $this->intervenantRepository->findInfosIntervenant((int) $iid)?->getTelPortable() ?? '';
        }
        // Flux « garde » : le téléphone a été saisi sur /garde et mémorisé en session
        // → on auto-identifie pour ne pas le redemander. (Consommé une fois.)
        if ($telPrefill === '' && ($gardeTel = $request->getSession()->get('garde_tel'))) {
            $telPrefill = (string) $gardeTel;
            $request->getSession()->remove('garde_tel');
        }

        // Contexte « garde d'enfant » (depuis /garde) : la prestation EST la garde → on
        // force le type ENFA et on masque le choix de service.
        $forceType = $request->query->get('garde') ? 'ENFA' : '';

        return $this->render('pointage/saisie.html.twig', [
            'numFam'     => $numFam,
            'nomFam'     => $nomFam,
            'telPrefill' => $telPrefill,
            'forceType'  => $forceType,
        ]);
    }

    // ── Déclaration GARDE D'ENFANT sans QR : téléphone → planning garde du jour ──
    #[Route('/garde', name: 'garde_saisie', methods: ['GET'])]
    public function garde(): Response
    {
        return $this->render('pointage/garde.html.twig');
    }

    #[Route('/garde/planning', name: 'garde_planning', methods: ['POST'])]
    public function gardePlanning(Request $request): JsonResponse
    {
        if (!$this->verifierCsrf($request)) {
            return $this->json(['success' => false, 'error' => 'Session expirée. Rechargez la page.'], 403);
        }
        if ($this->tropDEchecs($request)) {
            return $this->json(['success' => false, 'error' => 'Trop de tentatives. Réessayez dans quelques minutes.'], 429);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $interv = $this->intervenantRepository->findByTelephoneNormalise((string) ($data['tel'] ?? ''));
        if (!$interv) {
            $this->enregistrerEchec($request);
            return $this->json(['success' => false, 'error' => 'Numéro inconnu ou non reconnu. Vérifiez votre saisie.']);
        }

        $numInter = (int) $interv->getId();

        // Familles ayant un pointage EN COURS aujourd'hui (pour le badge).
        $enCours = [];
        foreach ($this->horaireService->getPointagesEnCours($numInter) as $p) {
            if (($p['aujourdhui'] ?? false) && !empty($p['numFam'])) {
                $enCours[(string) $p['numFam']] = true;
            }
        }

        $creneaux = [];
        foreach ($this->familleIntervenantService->getCreneauxGardeDuJour($numInter) as $c) {
            $famille = $this->familleService->getFamilleParNumero($c['numFam']);
            $creneaux[] = [
                'numFam'     => $c['numFam'],
                'nomFam'     => $this->familleExtension->familleLabel($famille ?? $c['numFam']) ?: $c['numFam'],
                'heureDebut' => $c['heureDebut'],
                'heureFin'   => $c['heureFin'],
                'enCours'    => isset($enCours[$c['numFam']]),
            ];
        }

        // Mémorise le téléphone (canonique) → /pointage/{numFam} auto-identifie sans le redemander.
        $request->getSession()->set('garde_tel', $interv->getTelPortable() ?? (string) ($data['tel'] ?? ''));

        return $this->json([
            'success'  => true,
            'nom'      => $interv->getNomCompletInter(),
            'creneaux' => $creneaux,
        ]);
    }

    #[Route('/pointage/{numFam}/identifier', name: 'pointage_identifier', methods: ['POST'])]
    public function identifier(string $numFam, Request $request): JsonResponse
    {
        if (!$this->verifierCsrf($request)) {
            return $this->json(['success' => false, 'error' => 'Session expirée. Rechargez la page.'], 403);
        }
        // Anti-énumération : on bloque seulement si trop d'ÉCHECS récents (numéros inconnus).
        // Les identifications réussies (dont l'auto-identif des connectés) ne comptent pas.
        if ($this->tropDEchecs($request)) {
            return $this->json(['success' => false, 'error' => 'Trop de tentatives. Réessayez dans quelques minutes.'], 429);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $interv = $this->intervenantRepository->findByTelephoneNormalise((string) ($data['tel'] ?? ''));

        if (!$interv) {
            $this->enregistrerEchec($request);
            return $this->json(['success' => false, 'error' => 'Numéro inconnu ou non reconnu. Vérifiez votre saisie.']);
        }

        $numInter = (int) $interv->getId();
        $fam      = $this->resoudreFamille($numInter, $numFam);

        // Assigné → planning de la famille ; non assigné → déclaration OCCASIONNELLE
        // (les deux types proposés, aucun créneau planning).
        if ($fam['assigned']) {
            $types    = array_values(array_map('strtoupper',
                $this->familleIntervenantService->getTypesParFamille($numInter)[$numFam] ?? []));
            $creneaux = $this->familleIntervenantService->getCreneauxDuJour($numInter, $numFam);
        } else {
            $types    = ['MENA', 'ENFA'];
            $creneaux = [];
        }

        $enCours = $this->horaireService->aPointageEnCours($numInter, $fam['effFam']);

        // Services réellement effectués par l'intervenant (d'après son planning, tous
        // familles confondues). On COMPTE les familles par type pour connaître son
        // service PRINCIPAL (le plus fréquent), trié en tête → défaut en occasionnel.
        $compteur = [];
        foreach ($this->familleIntervenantService->getTypesParFamille($numInter) as $typesFam) {
            foreach ($typesFam as $t) {
                $T = strtoupper($t);
                $compteur[$T] = ($compteur[$T] ?? 0) + 1;
            }
        }
        arsort($compteur); // service principal (le plus fréquent) en premier
        $servicesIntervenant = array_keys($compteur) ?: ['MENA', 'ENFA']; // repli : aucun planning → les 2

        // OUBLIS = pointages non terminés AUTRES que celui de la famille scannée du jour.
        // On les met en avant : il doit les clôturer AVANT de démarrer/terminer ici.
        $oublis = array_values(array_filter(
            $this->horaireService->getPointagesEnCours($numInter),
            fn($p) => !($p['aujourdhui'] && (string) $p['numFam'] === (string) $fam['effFam'])
        ));

        return $this->json([
            'success'             => true,
            'nom'                 => $interv->getNomCompletInter(),
            'nomFam'              => $fam['nomFam'],
            'occasionnel'         => !$fam['assigned'],
            'types'               => $types,
            'servicesIntervenant' => $servicesIntervenant,
            'oublis'      => $oublis,
            'enCours'     => $enCours,
            'typeEnCours' => $enCours ? $this->horaireService->typeEnCours($numInter, $fam['effFam']) : null,
            'debut'       => $enCours ? $this->horaireService->heureDebutEnCours($numInter, $fam['effFam']) : null,
            'km'          => $enCours ? $this->horaireService->kmEnCours($numInter, $fam['effFam']) : null,
            'creneaux'    => $creneaux,
            'heureNow'    => (new \DateTime())->format('H:i'),
        ]);
    }

    #[Route('/pointage/{numFam}/demarrer', name: 'pointage_demarrer', methods: ['POST'])]
    public function demarrer(string $numFam, Request $request): JsonResponse
    {
        if (!$this->verifierCsrf($request)) {
            return $this->json(['success' => false, 'error' => 'Session expirée. Rechargez la page.'], 403);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $interv = $this->intervenantRepository->findByTelephoneNormalise((string) ($data['tel'] ?? ''));
        if (!$interv) {
            return $this->json(['success' => false, 'error' => 'Numéro non reconnu.'], 422);
        }

        $numInter = (int) $interv->getId();
        $fam      = $this->resoudreFamille($numInter, $numFam);

        try {
            $km = isset($data['km']) && $data['km'] !== null && $data['km'] !== '' ? (float) $data['km'] : null;
            $this->horaireService->demarrerPointage(
                $numInter,
                $fam['effFam'],
                $fam['nomFam'],
                (string) ($data['type'] ?? 'ENFA'),
                $this->heureClient($data['heure'] ?? null),
                $this->heureClient($data['heureReelle'] ?? null),
                $km,
            );
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        $this->logger->info('Pointage QR démarré (sans connexion)', [
            'numInter' => $numInter, 'numFam' => $fam['effFam'], 'occasionnel' => !$fam['assigned'],
        ]);

        return $this->json(['success' => true, 'action' => 'demarre']);
    }

    #[Route('/pointage/{numFam}/modifier', name: 'pointage_modifier', methods: ['POST'])]
    public function modifier(string $numFam, Request $request): JsonResponse
    {
        if (!$this->verifierCsrf($request)) {
            return $this->json(['success' => false, 'error' => 'Session expirée. Rechargez la page.'], 403);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $interv = $this->intervenantRepository->findByTelephoneNormalise((string) ($data['tel'] ?? ''));
        if (!$interv) {
            return $this->json(['success' => false, 'error' => 'Numéro non reconnu.'], 422);
        }

        $numInter = (int) $interv->getId();
        $fam      = $this->resoudreFamille($numInter, $numFam);

        try {
            $horaire = $this->horaireService->modifierEnCours(
                $numInter,
                $fam['effFam'],
                (string) $this->heureClient($data['heureDebut'] ?? null),
                (string) $this->heureClient($data['heureFin'] ?? null),
            );
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        return $this->json([
            'success' => true,
            'debut'   => $horaire->getHeureDebutPresta()?->format('H:i'),
            'fin'     => $horaire->getHeureFinPresta()?->format('H:i'),
        ]);
    }

    #[Route('/pointage/{numFam}/cloturer', name: 'pointage_cloturer', methods: ['POST'])]
    public function cloturer(string $numFam, Request $request): JsonResponse
    {
        if (!$this->verifierCsrf($request)) {
            return $this->json(['success' => false, 'error' => 'Session expirée. Rechargez la page.'], 403);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $interv = $this->intervenantRepository->findByTelephoneNormalise((string) ($data['tel'] ?? ''));
        if (!$interv) {
            return $this->json(['success' => false, 'error' => 'Numéro non reconnu.'], 422);
        }

        try {
            $this->horaireService->cloturerPointageOublie(
                (int) $interv->getId(),
                (int) ($data['horaireId'] ?? 0),
                (string) ($data['heure'] ?? ''),
                $this->heureClient($data['heureDebut'] ?? null),
            );
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }

    #[Route('/pointage/{numFam}/terminer', name: 'pointage_terminer', methods: ['POST'])]
    public function terminer(string $numFam, Request $request): JsonResponse
    {
        if (!$this->verifierCsrf($request)) {
            return $this->json(['success' => false, 'error' => 'Session expirée. Rechargez la page.'], 403);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $interv = $this->intervenantRepository->findByTelephoneNormalise((string) ($data['tel'] ?? ''));
        if (!$interv) {
            return $this->json(['success' => false, 'error' => 'Numéro non reconnu.'], 422);
        }

        $km = isset($data['km']) && $data['km'] !== null && $data['km'] !== '' ? (float) $data['km'] : null;

        $numInter = (int) $interv->getId();
        $fam      = $this->resoudreFamille($numInter, $numFam);

        try {
            $horaire = $this->horaireService->terminerPointage(
                $numInter,
                $fam['effFam'],
                $km,
                $this->heureClient($data['heure'] ?? null),
                $this->heureClient($data['heureReelle'] ?? null),
                $this->heureClient($data['heureDebut'] ?? null),
            );
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        $this->logger->info('Pointage QR terminé (sans connexion)', [
            'numInter' => $numInter, 'numFam' => $fam['effFam'],
        ]);

        return $this->json([
            'success' => true,
            'action'  => 'termine',
            'debut'   => $horaire->getHeureDebutPresta()?->format('H:i'),
            'fin'     => $horaire->getHeureFinPresta()?->format('H:i'),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Résout la famille pour le pointage :
     *  - intervenant ASSIGNÉ (planning PREST)  → famille réelle (numFam),
     *  - NON assigné                           → déclaration OCCASIONNELLE (numFam='0'),
     *    en conservant le vrai nom de la famille (via famille_label).
     *
     * @return array{assigned:bool, effFam:string, nomFam:string}
     */
    private function resoudreFamille(int $numInter, string $numFam): array
    {
        $assigned = $this->familleIntervenantService->peutPointer($numInter, $numFam);
        $famille  = $this->familleService->getFamilleParNumero($numFam);
        $nomFam   = $this->familleExtension->familleLabel($famille ?? $numFam) ?: $numFam;

        return [
            'assigned' => $assigned,
            // Le scan identifie une VRAIE famille → on garde toujours le numFam réel.
            // Le caractère « occasionnel » (hors planning) est porté par le flag de la
            // prestation, pas par un numFam vide.
            'effFam'   => $numFam,
            'nomFam'   => $nomFam,
        ];
    }

    /**
     * Valide l'heure envoyée par le téléphone (format HH:MM strict). Retourne null
     * si invalide → le service retombe sur l'heure serveur.
     */
    private function heureClient(mixed $heure): ?string
    {
        return is_string($heure) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $heure) ? $heure : null;
    }

    private function verifierCsrf(Request $request): bool
    {
        return $this->isCsrfTokenValid('pointage', $request->headers->get('X-CSRF-Token', ''));
    }

    /**
     * Rate-limit léger par session (anti-énumération de numéros) pas de dépendance
     * externe : compteur glissant sur FENETRE_SECONDES.
     */
    /** Vrai si trop d'échecs récents (numéros inconnus) sur la fenêtre glissante. */
    private function tropDEchecs(Request $request): bool
    {
        $data = $request->getSession()->get('pointage_rl', ['count' => 0, 'start' => time()]);
        if (time() - $data['start'] > self::FENETRE_SECONDES) {
            return false; // fenêtre expirée → reparti à zéro
        }
        return $data['count'] >= self::MAX_TENTATIVES;
    }

    /** Incrémente le compteur d'échecs (uniquement sur numéro inconnu). */
    private function enregistrerEchec(Request $request): void
    {
        $session = $request->getSession();
        $now     = time();
        $data    = $session->get('pointage_rl', ['count' => 0, 'start' => $now]);

        if ($now - $data['start'] > self::FENETRE_SECONDES) {
            $data = ['count' => 0, 'start' => $now];
        }
        $data['count']++;
        $session->set('pointage_rl', $data);
    }
}
