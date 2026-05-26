<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\FamilleIntervenantService;
use App\Service\HoraireinterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class HeuresControllerMVC extends AbstractController
{
    public function __construct(
        private AuthService              $authService,
        private HoraireinterService      $horaireService,
        private FamilleIntervenantService $familleIntervenantService,
    ) {}

    /**
     * Support JSON + FormData
     */
    private function getData(Request $request): array
    {
        $contentType = $request->headers->get('Content-Type');

        if ($contentType && str_starts_with($contentType, 'application/json')) {
            return json_decode($request->getContent(), true) ?? [];
        }

        return $request->request->all();
    }

    #[Route('/heures-mvc/ajouter', name: 'heures_ajouter_mvc', methods: ['POST'])]
    public function ajouterHeure(Request $request): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $data = $this->getData($request);

        if (empty($data['date']) || empty($data['heureDebut']) || empty($data['heureFin'])) {
            return $this->json([
                'success' => false,
                'error' => 'Champs obligatoires manquants'
            ], 400);
        }

        $numFam   = $data['famille'] ?? $data['numFam'] ?? null;
        $numInter = $this->authService->intervenant_id();

        // Famille occasionnelle : value "0" côté JS → numFam null ou "0"
        $isFamilleOccasionnelle = ($numFam === null || $numFam === '' || $numFam === '0' || $numFam == 0);

        if (!$isFamilleOccasionnelle && !$this->authService->isAdmin()) {
            if (!$this->familleIntervenantService->peutPointer((int) $numInter, (string) $numFam)) {
                return $this->json([
                    'success' => false,
                    'error'   => 'Vous n\'êtes pas assigné à cette famille.',
                ]);
            }
        }

        $donnees = [
            'numFam'           => $isFamilleOccasionnelle ? null : $numFam,
            'nomFam'           => $data['nomRemplacement'] ?? $data['nomFam'] ?? null,
            'numInter'         => $numInter,
            'datePresta'       => $data['date'] ?? $data['datePresta'] ?? null,
            'heureDebutPresta' => ($data['heureDebut'] ?? '') . ':' . ($data['minuteDebut'] ?? ''),
            'heureFinPresta'   => ($data['heureFin'] ?? '') . ':' . ($data['minuteFin'] ?? ''),
            'typePresta'       => $data['type'] ?? $data['typePresta'] ?? null,
            'kmAvecEnfant'     => $data['trajet'] ?? $data['kmAvecEnfant'] ?? 0,
        ];

        try {
            $horaire = $this->horaireService->ajouterPrestation($donnees);

            return $this->json([
                'success' => true,
                'id' => $horaire->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/heures-mvc/modifier/{id}', name: 'heures_modifier_mvc', methods: ['POST'])]
    public function modifierHeure(int $id, Request $request): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $isAdmin = $this->authService->isAdmin();
        $horaire = $this->horaireService->getPrestation($id);

        if ($horaire && !$isAdmin && $this->horaireService->isVerrouille($horaire)) {
            return $this->json([
                'success' => false,
                'locked'  => true,
                'error'   => 'Cette prestation appartient à un mois passé et ne peut plus être modifiée.',
            ], 423);
        }

        $data = $this->getData($request);

        $donnees = [
            'datePresta'       => $data['date'] ?? $data['datePresta'] ?? null,
            'heureDebutPresta' => ($data['heureDebut'] ?? '') . ':' . ($data['minuteDebut'] ?? ''),
            'heureFinPresta'   => ($data['heureFin'] ?? '') . ':' . ($data['minuteFin'] ?? ''),
            'kmAvecEnfant'     => $data['trajet'] ?? $data['kmAvecEnfant'] ?? 0,
        ];

        try {
            $ok = $this->horaireService->modifierPrestation($id, $donnees, $isAdmin);
        } catch (\LogicException $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 409);
        }

        return $this->json([
            'success' => $ok,
            'error'   => $ok ? null : 'Impossible de modifier cette prestation',
        ], $ok ? 200 : 400);
    }

    #[Route('/heures-mvc/supprimer/{id}', name: 'heures_supprimer_mvc', methods: ['POST'])]
    public function supprimerHeure(int $id): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $isAdmin = $this->authService->isAdmin();
        $horaire = $this->horaireService->getPrestation($id);

        if ($horaire && !$isAdmin && $this->horaireService->isVerrouille($horaire)) {
            return $this->json([
                'success' => false,
                'locked'  => true,
                'error'   => 'Cette prestation appartient à un mois passé et ne peut plus être supprimée.',
            ], 423);
        }

        $ok = $this->horaireService->desactiverPrestation($id, $isAdmin);

        return $this->json([
            'success' => $ok,
            'error'   => $ok ? null : 'Impossible de supprimer cette prestation',
        ]);
    }

    #[Route('/heures-mvc/restaurer/{id}', name: 'heures_restaurer_mvc', methods: ['POST'])]
    public function restaurerHeure(int $id): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $ok = $this->horaireService->restaurerPrestation($id);

        return $this->json([
            'success' => $ok,
            'error'   => $ok ? null : 'Impossible de restaurer cette prestation',
        ]);
    }

    #[Route('/heures-mvc/ajouter-hors-structure/{intervenantId}', name: 'heures_ajouter_hors_structure_mvc', methods: ['POST'])]
    public function ajouterHeureHorsStructure(int $intervenantId, Request $request): JsonResponse
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }

        $data = $this->getData($request);

        $donnees = [
            'numFam' => null,
            'nomFam' => 'HORS STRUCTURE',
            'numInter' => $intervenantId,
            'datePresta' => $data['date'] ?? $data['datePresta'] ?? null,
            'heureDebutPresta' => ($data['heureDebut'] ?? '') . ':' . ($data['minuteDebut'] ?? ''),
            'heureFinPresta' => ($data['heureFin'] ?? '') . ':' . ($data['minuteFin'] ?? ''),
            'typePresta' => $data['type'] ?? $data['typePresta'] ?? null,
            'kmAvecEnfant' => $data['trajet'] ?? $data['kmAvecEnfant'] ?? 0,
        ];

        try {
            $horaire = $this->horaireService->ajouterPrestation($donnees);

            return $this->json([
                'success' => true,
                'id' => $horaire->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/heures-mvc/get/{id}', name: 'heures_get_mvc', methods: ['GET'])]
    public function getHeure(int $id): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $horaire = $this->horaireService->getPrestation($id);

        if (!$horaire) {
            return $this->json(['success' => false, 'error' => 'Prestation introuvable'], 404);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id'               => $horaire->getId(),
                'numFam'           => $horaire->getNumFam(),
                'nomFam'           => $horaire->getNomFam(),
                'datePresta'       => $horaire->getDatePresta()->format('Y-m-d'),
                'heureDebutPresta' => $horaire->getHeureDebutPresta()->format('H:i'),
                'heureFinPresta'   => $horaire->getHeureFinPresta()->format('H:i'),
                'typePresta'       => $horaire->getTypePresta(),
                'kmAvecEnfant'     => $horaire->getKmAvecEnfant(),
                'verrouille'       => !$this->authService->isAdmin() && $this->horaireService->isVerrouille($horaire),
            ]
        ]);
    }

    #[Route('/heures-mvc/liste/{intervenantId}', name: 'heures_liste_mvc', methods: ['GET'])]
    public function listeHeures(int $intervenantId, Request $request): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $dateDebut = $request->query->get('dateDebut') ? new \DateTime($request->query->get('dateDebut')) : null;
        $dateFin = $request->query->get('dateFin') ? new \DateTime($request->query->get('dateFin')) : null;

        $prestations = $this->horaireService->getPrestationsParIntervenant($intervenantId, $dateDebut, $dateFin);

        $donnees = [];
        foreach ($prestations as $prestation) {
            $donnees[] = [
                'id' => $prestation->getId(),
                'nomFam' => $prestation->getNomFam(),
                'datePresta' => $prestation->getDatePresta()->format('Y-m-d'),
                'heureDebutPresta' => $prestation->getHeureDebutPresta()->format('H:i'),
                'heureFinPresta' => $prestation->getHeureFinPresta()->format('H:i'),
                'typePresta' => $prestation->getTypePresta(),
                'kmAvecEnfant' => $prestation->getKmAvecEnfant(),
                'declarerLeFam' => $prestation->getDeclarerLeFam()?->format('d/m/Y H:i'),
                'desactiver' => $prestation->isDesactiver(),
                'ajouterLe' => $prestation->getAjouterLe()->format('d/m/Y H:i'),
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $donnees
        ]);
    }

    #[Route('/heures-mvc/proposer-info', name: 'proposer_info_mvc', methods: ['GET'])]
    public function getProposerInfo(Request $request): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $raw = $request->query->get('proposer', '');
        $key = json_decode($raw, true);

        if (!is_array($key) || !isset($key['s'], $key['f'], $key['t'], $key['a'], $key['j'], $key['h'])) {
            return $this->json(['success' => false, 'error' => 'Clé invalide'], 400);
        }

        $data = $this->familleIntervenantService->getProposerPrefill(
            (int)$key['s'],
            (string)$key['f'],
            (string)$key['t'],
            (string)$key['a'],
            (string)$key['j'],
            (string)$key['h'],
        );

        if (!$data) {
            return $this->json(['success' => false, 'error' => 'Proposer introuvable'], 404);
        }

        return $this->json(['success' => true, 'data' => $data]);
    }

    #[Route('/heures-mvc/peut-saisir', name: 'heures_peut_saisir_mvc', methods: ['POST'])]
    public function peutSaisirHeures(Request $request): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $data = $this->getData($request);

        $dateSaisie = new \DateTime($data['dateSaisie'] ?? 'now');

        $peutSaisir = $this->horaireService->peutSaisirHeures($dateSaisie);

        return $this->json([
            'success' => true,
            'peutSaisir' => $peutSaisir,
            'dateLimite' => (new \DateTime())->sub(new \DateInterval('P7D'))->format('d/m/Y')
        ]);
    }
}