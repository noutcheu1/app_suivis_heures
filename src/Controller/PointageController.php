<?php

namespace App\Controller;

use App\Repository\IntervenantRepository;
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
 * authentifiée n'est créée — on ré-identifie l'intervenant par son numéro à chaque
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
        private LoggerInterface           $logger,
    ) {}

    #[Route('/pointage/{numFam}', name: 'pointage_saisie', methods: ['GET'])]
    public function saisie(string $numFam): Response
    {
        $famille = $this->familleService->getFamilleParNumero($numFam);
        $nomFam  = $this->familleExtension->familleLabel($famille ?? $numFam) ?: $numFam;

        return $this->render('pointage/saisie.html.twig', [
            'numFam' => $numFam,
            'nomFam' => $nomFam,
        ]);
    }

    #[Route('/pointage/{numFam}/identifier', name: 'pointage_identifier', methods: ['POST'])]
    public function identifier(string $numFam, Request $request): JsonResponse
    {
        if (!$this->verifierCsrf($request)) {
            return $this->json(['success' => false, 'error' => 'Session expirée. Rechargez la page.'], 403);
        }
        if (!$this->autoriserTentative($request)) {
            return $this->json(['success' => false, 'error' => 'Trop de tentatives. Réessayez dans quelques minutes.'], 429);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $interv = $this->intervenantRepository->findByTelephoneNormalise((string) ($data['tel'] ?? ''));

        if (!$interv) {
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

        return $this->json([
            'success'     => true,
            'nom'         => $interv->getNomCompletInter(),
            'nomFam'      => $fam['nomFam'],
            'occasionnel' => !$fam['assigned'],
            'types'       => $types,
            'enCours'     => $enCours,
            'typeEnCours' => $enCours ? $this->horaireService->typeEnCours($numInter, $fam['effFam']) : null,
            'debut'       => $enCours ? $this->horaireService->heureDebutEnCours($numInter, $fam['effFam']) : null,
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
            $this->horaireService->demarrerPointage(
                $numInter,
                $fam['effFam'],
                $fam['nomFam'],
                (string) ($data['type'] ?? 'ENFA'),
                $this->heureClient($data['heure'] ?? null),
            );
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        $this->logger->info('Pointage QR démarré (sans connexion)', [
            'numInter' => $numInter, 'numFam' => $fam['effFam'], 'occasionnel' => !$fam['assigned'],
        ]);

        return $this->json(['success' => true, 'action' => 'demarre']);
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
            $horaire = $this->horaireService->terminerPointage($numInter, $fam['effFam'], $km, $this->heureClient($data['heure'] ?? null));
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
     * @return array{assigned:bool, effFam:?string, nomFam:string}
     */
    private function resoudreFamille(int $numInter, string $numFam): array
    {
        $assigned = $this->familleIntervenantService->peutPointer($numInter, $numFam);
        $famille  = $this->familleService->getFamilleParNumero($numFam);
        $nomFam   = $this->familleExtension->familleLabel($famille ?? $numFam) ?: $numFam;

        return [
            'assigned' => $assigned,
            // Non assigné → occasionnel : numFam à NULL (comme la saisie manuelle),
            // le nom réel étant conservé dans nomFam.
            'effFam'   => $assigned ? $numFam : null,
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
     * Rate-limit léger par session (anti-énumération de numéros) — pas de dépendance
     * externe : compteur glissant sur FENETRE_SECONDES.
     */
    private function autoriserTentative(Request $request): bool
    {
        $session = $request->getSession();
        $now     = time();
        $data    = $session->get('pointage_rl', ['count' => 0, 'start' => $now]);

        if ($now - $data['start'] > self::FENETRE_SECONDES) {
            $data = ['count' => 0, 'start' => $now];
        }

        $data['count']++;
        $session->set('pointage_rl', $data);

        return $data['count'] <= self::MAX_TENTATIVES;
    }
}
