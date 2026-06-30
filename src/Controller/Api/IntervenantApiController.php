<?php

namespace App\Controller\Api;

use App\Service\AuthService;
use App\Service\FamilleService;
use App\Service\HoraireinterService;
use App\Service\IntervenantService;
use App\Service\ReleveMailService;
use App\Repository\UserSuiviRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/intervenants')]
final class IntervenantApiController extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private HoraireinterService $horaireService,
        private IntervenantService $intervenantService,
        private FamilleService $familleService,
        private ReleveMailService $releveMailService,
        private UserSuiviRepository $userSuiviRepository,
    ) {}

    /**
     * Retourne les données du relevé mensuel d'un intervenant.
     * Paramètres GET : type (ENFA|MENA), mois (offset entier, 0 = mois courant, -1 = précédent)
     */
    #[Route('/releve/{id}', name: 'api_intervenants_releve', methods: ['GET'])]
    public function releve(int $id, Request $request): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        if (!$this->authService->isAdmin() && $this->authService->intervenant_id() !== $id) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $type       = strtoupper($request->query->get('type', 'ENFA'));
        $moisOffset = (int)$request->query->get('mois', 0);

        $intervenant = $this->intervenantService->getInfosIntervenant($id);
        if (!$intervenant) {
            return $this->json(['error' => 'Intervenant introuvable'], 404);
        }

        $numInter = $intervenant->getLegacyNumInter() ?? $id;
        $data = $this->horaireService->getReleveData($numInter, $type, $moisOffset, $intervenant);

        // Enrichir chaque famille avec sa ville (lookup depuis l'entité Famille)
        foreach ($data['familles'] as &$fam) {
            $famille = !empty($fam['numFam'])
                ? $this->familleService->getFamilleParNumero($fam['numFam'])
                : null;
            $fam['ville_Famille'] = $famille?->getVille() ?? '';
        }
        unset($fam);

        return $this->json($data);
    }

    /**
     * Signe le relevé du mois courant ou du mois précédent.
     * Paramètres GET : type (ENFA|MENA), periode_fin (Y-m-d, dernier jour du mois concerné)
     */
    #[Route('/{id}/signer', name: 'api_intervenants_signer', methods: ['GET'])]
    public function signer(int $id, Request $request): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        if (!$this->authService->isAdmin() && $this->authService->intervenant_id() !== $id) {
            return $this->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $type       = strtoupper($request->query->get('type', 'ENFA'));
        $periodeFin = $request->query->get('periode_fin');

        if (!$periodeFin) {
            return $this->json(['success' => false, 'message' => 'Paramètre periode_fin manquant'], 400);
        }

        $intervenant = $this->intervenantService->getInfosIntervenant($id);
        $numInter    = $intervenant ? ($intervenant->getLegacyNumInter() ?? $id) : $id;
        $result = $this->horaireService->signerReleve($numInter, $type, $periodeFin);

        return $this->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Signe le relevé ET l'envoie par email (PDF en pièce jointe généré côté navigateur)
     * à l'intervenant, avec la structure Chaudoudoux en copie.
     * Corps JSON : { type, periodeFin (Y-m-d), pdfBase64 (data URI), filename }
     */
    #[Route('/{id}/signer-email', name: 'api_intervenants_signer_email', methods: ['POST'])]
    public function signerEtEnvoyer(int $id, Request $request): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }
        if (!$this->authService->isAdmin() && $this->authService->intervenant_id() !== $id) {
            return $this->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $data       = json_decode($request->getContent(), true) ?? [];
        $type       = strtoupper($data['type'] ?? 'ENFA');
        $periodeFin = $data['periodeFin'] ?? null;

        if (!$periodeFin) {
            return $this->json(['success' => false, 'message' => 'Paramètre periodeFin manquant'], 400);
        }

        $intervenant = $this->intervenantService->getInfosIntervenant($id);
        $numInter    = $intervenant ? ($intervenant->getLegacyNumInter() ?? $id) : $id;

        // Email destinataire : celui enregistré dans users_suivi (login = numSS),
        // avec repli sur l'email du dossier intervenant.
        $emailDest = null;
        if ($intervenant?->getNumSs()) {
            $emailDest = $this->userSuiviRepository->findByIdentifiant($intervenant->getNumSs())?->getEmail();
        }
        $emailDest ??= $intervenant?->getEmail();

        // Nom de fichier : releve_nom_intervenant_service_mois.pdf (construit côté serveur)
        $service  = $type === 'MENA' ? 'Menage' : 'Garde';
        $mois     = (new \DateTime($periodeFin))->format('m-Y');
        $nomBrut  = trim(($intervenant?->getNom() ?? '') . '_' . ($intervenant?->getPrenom() ?? ''), '_ ');
        $nomAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nomBrut) ?: $nomBrut;
        $nomSlug  = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $nomAscii), '_') ?: 'intervenant';
        $filename = sprintf('releve_%s_%s_%s.pdf', $nomSlug, $service, $mois);

        $result = $this->releveMailService->signerEtEnvoyer(
            $numInter,
            $type,
            $periodeFin,
            $emailDest,
            $data['pdfBase64'] ?? null,
            $filename,
        );

        return $this->json($result, $result['success'] ? 200 : 400);
    }
    
    
    /**
     * Enregistre des heures travaillées hors structure pour un mois.
     * Corps JSON : { heure, minute, periodeFin (Y-m-d), type }
     */
    #[Route('/{id}/ajout_heure_de_hors', name: 'api_intervenants_heure_dehors', methods: ['POST'])]
    public function ajouterHeureDehors(int $id, Request $request): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        if (!$this->authService->isAdmin() && $this->authService->intervenant_id() !== $id) {
            return $this->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $heure      = (int)($data['heure'] ?? 0);
        $minute     = (int)($data['minute'] ?? 0);
        $periodeFin = $data['periodeFin'] ?? null;
        $type       = strtoupper($data['type'] ?? 'MENA');

        if (!$periodeFin) {
            return $this->json(['success' => false, 'message' => 'Paramètre periodeFin manquant'], 400);
        }

        $intervenant = $this->intervenantService->getInfosIntervenant($id);
        $numInter    = $intervenant ? ($intervenant->getLegacyNumInter() ?? $id) : $id;
        $result = $this->horaireService->ajouterHeuresHorsStructure($numInter, $heure, $minute, $periodeFin, $type);

        return $this->json($result);
    }
}
