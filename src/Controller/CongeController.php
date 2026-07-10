<?php

namespace App\Controller;

use App\Entity\Horaire\Conge;
use App\Service\AuthService;
use App\Service\CongeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Déclaration des congés d'une personne (famille ou intervenant), identifiée dans
 * l'URL — même principe que les relevés/facture famille : accessible à la personne
 * elle-même OU à un admin. CRUD complet, indépendant de toute campagne.
 */
final class CongeController extends AbstractController
{
    private const TYPES = [Conge::PERSONNE_FAMILLE, Conge::PERSONNE_INTERVENANT];

    public function __construct(
        private AuthService  $authService,
        private CongeService $congeService,
        private \App\Repository\IntervenantDispoRepository $dispoRepo,
    ) {}

    #[Route('/conges/{type}/{personneId}', name: 'mes_conges_mvc', requirements: ['type' => 'FAMILLE|INTERVENANT', 'personneId' => '[A-Za-z0-9]+'])]
    public function index(string $type, string $personneId): Response
    {
        if (!$this->acces($type, $personneId)) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('conges/index.html.twig', [
            'auth'       => true,
            'type'       => $type,
            'personneId' => $personneId,
            'conges'     => $this->congeService->listerPour($type, $personneId),
            'dispoRempl' => $type === Conge::PERSONNE_INTERVENANT
                ? $this->dispoRepo->estDisponible((int) $personneId) : false,
            'estAdmin'   => $this->authService->isAdmin(),
        ]);
    }

    #[Route('/conges/INTERVENANT/{personneId}/dispo', name: 'mes_conges_dispo_mvc', methods: ['POST'], requirements: ['personneId' => '[A-Za-z0-9]+'])]
    public function dispo(string $personneId, Request $request): Response
    {
        if (!$this->acces(Conge::PERSONNE_INTERVENANT, $personneId)) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->isCsrfTokenValid('conge_dispo_' . $personneId, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->retour(Conge::PERSONNE_INTERVENANT, $personneId);
        }

        $this->dispoRepo->definir((int) $personneId, $request->request->get('disponible') === 'oui');
        $this->addFlash('success', 'Votre disponibilité a été mise à jour.');
        return $this->retour(Conge::PERSONNE_INTERVENANT, $personneId);
    }

    #[Route('/conges/{type}/{personneId}/ajouter', name: 'mes_conges_ajouter_mvc', methods: ['POST'], requirements: ['type' => 'FAMILLE|INTERVENANT', 'personneId' => '[A-Za-z0-9]+'])]
    public function ajouter(string $type, string $personneId, Request $request): Response
    {
        if (!$this->acces($type, $personneId)) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->isCsrfTokenValid('conge_ajouter', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->retour($type, $personneId);
        }

        $res = $this->congeService->ajouter(
            $type, $personneId,
            $request->request->get('dateDebut'),
            $request->request->get('dateFin'),
            $request->request->get('motif'),
            $this->attributs($type, $request),
        );
        // La réponse « disponible pour remplacements » (dans le formulaire) met à jour
        // la disponibilité GÉNÉRALE de l'intervenant (indépendante de ce congé).
        if ($type === Conge::PERSONNE_INTERVENANT && ($d = $request->request->get('disponible')) !== null) {
            $this->dispoRepo->definir((int) $personneId, $d === 'oui');
        }
        $this->addFlash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Congé enregistré.' : $res['erreur']);
        return $this->retour($type, $personneId);
    }

    #[Route('/conges/{type}/{personneId}/{id}/modifier', name: 'mes_conges_modifier_mvc', methods: ['POST'], requirements: ['type' => 'FAMILLE|INTERVENANT', 'personneId' => '[A-Za-z0-9]+', 'id' => '\d+'])]
    public function modifier(string $type, string $personneId, int $id, Request $request): Response
    {
        if (!$this->acces($type, $personneId)) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->isCsrfTokenValid('conge_modifier_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->retour($type, $personneId);
        }

        $res = $this->congeService->modifier(
            $id, $type, $personneId,
            $request->request->get('dateDebut'),
            $request->request->get('dateFin'),
            $request->request->get('motif'),
            $this->attributs($type, $request),
            $this->authService->isAdmin(),
        );
        $this->addFlash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Congé mis à jour.' : $res['erreur']);
        return $this->retour($type, $personneId);
    }

    #[Route('/conges/{type}/{personneId}/{id}/supprimer', name: 'mes_conges_supprimer_mvc', methods: ['POST'], requirements: ['type' => 'FAMILLE|INTERVENANT', 'personneId' => '[A-Za-z0-9]+', 'id' => '\d+'])]
    public function supprimer(string $type, string $personneId, int $id, Request $request): Response
    {
        if (!$this->acces($type, $personneId)) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->isCsrfTokenValid('conge_supprimer_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->retour($type, $personneId);
        }

        $ok = $this->congeService->annuler($id, $type, $personneId, $this->authService->isAdmin());
        $this->addFlash($ok ? 'success' : 'error', $ok ? 'Congé supprimé.' : 'Suppression impossible (congé introuvable ou déjà validé).');
        return $this->retour($type, $personneId);
    }

    // ── Validation admin (intervenants) ──────────────────────────────────────

    #[Route('/admin-mvc/conges/{id}/valider', name: 'admin_conge_valider_mvc', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function valider(int $id, Request $request): Response
    {
        return $this->statutAdmin($id, $request, true);
    }

    #[Route('/admin-mvc/conges/{id}/refuser', name: 'admin_conge_refuser_mvc', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function refuser(int $id, Request $request): Response
    {
        return $this->statutAdmin($id, $request, false);
    }

    #[Route('/admin-mvc/conges/{id}/devalider', name: 'admin_conge_devalider_mvc', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function devalider(int $id, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->isCsrfTokenValid('conge_statut_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_conges_mvc');
        }
        $ok = $this->congeService->remettreEnAttente($id);
        $this->addFlash($ok ? 'success' : 'error', $ok ? 'Congé remis en attente (modifiable).' : 'Congé introuvable.');
        return $this->redirectToRoute('admin_conges_mvc');
    }

    private function statutAdmin(int $id, Request $request, bool $valider): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->isCsrfTokenValid('conge_statut_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_conges_mvc');
        }

        $ok = $valider ? $this->congeService->valider($id) : $this->congeService->refuser($id);
        $this->addFlash($ok ? 'success' : 'error', $ok ? ($valider ? 'Congé validé.' : 'Congé refusé.') : 'Congé introuvable.');
        return $this->redirectToRoute('admin_conges_mvc');
    }

    // ── Interne ───────────────────────────────────────────────────────────────

    /** Accès autorisé : admin, OU la personne elle-même (même principe que les relevés). */
    private function acces(string $type, string $personneId): bool
    {
        if (!$this->authService->check() || !in_array($type, self::TYPES, true)) {
            return false;
        }
        if ($this->authService->isAdmin()) {
            return true;
        }
        if ($type === Conge::PERSONNE_FAMILLE) {
            return (string) $this->authService->famille_id() === $personneId;
        }
        return (string) $this->authService->intervenant_id() === $personneId;
    }

    private function retour(string $type, string $personneId): Response
    {
        return $this->redirectToRoute('mes_conges_mvc', ['type' => $type, 'personneId' => $personneId]);
    }

    /** Attributs métier selon le type de personne. */
    private function attributs(string $type, Request $request): array
    {
        if ($type === Conge::PERSONNE_FAMILLE) {
            $m = $request->request->get('maintienPrestation');
            $r = $request->request->get('souhaiteRemplacement');
            return [
                'maintienPrestation'   => $m === null ? null : $m === 'oui',
                'souhaiteRemplacement' => $r === null ? null : $r === 'oui',
            ];
        }
        $d = $request->request->get('disponibleRemplacement');
        return ['disponibleRemplacement' => $d === null ? null : $d === 'oui'];
    }
}
