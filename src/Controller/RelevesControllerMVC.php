<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\HoraireinterService;
use App\Service\IntervenantService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour la gestion des relevés mensuels selon le pattern MVC
 */
final class RelevesControllerMVC extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private HoraireinterService $horaireService,
        private IntervenantService $intervenantService
    ) {}

    #[Route('/releves-mvc/intervenant/{intervenantId}', name: 'releves_intervenant_mvc')]
    public function relevesIntervenant(int $intervenantId, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier que l'intervenant peut voir ses propres relevés ou que c'est un admin
        if (!$this->authService->isAdmin() && $this->authService->intervenant_id() !== $intervenantId) {
            return $this->redirectToRoute('dashboard');
        }

        $mois = $request->query->get('mois', date('m/Y'));
        
        // TODO: Implémenter le service de relevés mensuels
        $releves = [];
        $heures = $this->horaireService->getPrestationsParIntervenant($intervenantId);

        return $this->render('releves/intervenant.html.twig', [
            'auth' => $this->authService->check(),
            'intervenantId' => $intervenantId,
            'mois' => $mois,
            'releves' => $releves,
            'heures' => $heures,
        ]);
    }

    #[Route('/releves-mvc/famille/{numFam}', name: 'releves_famille_mvc')]
    public function relevesFamille(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        // TODO: Implémenter la vérification que la famille peut voir ses propres relevés
        $mois = $request->query->get('mois', date('m/Y'));
        
        // TODO: Implémenter le service de relevés mensuels familles
        $releves = [];
        $heures = $this->horaireService->getPrestationsParFamille($numFam);

        return $this->render('releves/famille.html.twig', [
            'auth' => $this->authService->check(),
            'numFam' => $numFam,
            'mois' => $mois,
            'releves' => $releves,
            'heures' => $heures,
        ]);
    }

    #[Route('/releves-mvc/intervenant/{intervenantId}/pdf', name: 'releves_intervenant_pdf_mvc')]
    public function relevesIntervenantPdf(int $intervenantId, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->query->get('mois', date('m/Y'));
        
        // TODO: Implémenter la génération PDF pour les relevés intervenants
        $pdfContent = "PDF Relevé Intervenant {$intervenantId} - {$mois}";
        
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', "attachment; filename=\"releve_intervenant_{$intervenantId}_{$mois}.pdf\"");
        
        return $response;
    }

    #[Route('/releves-mvc/famille/{numFam}/pdf', name: 'releves_famille_pdf_mvc')]
    public function relevesFamillePdf(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->query->get('mois', date('m/Y'));
        
        // TODO: Implémenter la génération PDF pour les relevés familles
        $pdfContent = "PDF Relevé Famille {$numFam} - {$mois}";
        
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', "attachment; filename=\"releve_famille_{$numFam}_{$mois}.pdf\"");
        
        return $response;
    }

    #[Route('/releves-mvc/intervenant/{intervenantId}/signer', name: 'releves_intervenant_signer_mvc', methods: ['POST'])]
    public function signerReleveIntervenant(int $intervenantId, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->request->get('mois');
        $typePresta = $request->request->get('typePresta');
        
        // TODO: Implémenter le service de signature de relevé
        // $this->releveService->signerReleveIntervenant($intervenantId, $mois, $typePresta);
        
        $this->addFlash('success', 'Relevé signé avec succès');
        
        return $this->redirectToRoute('releves_intervenant_mvc', ['intervenantId' => $intervenantId, 'mois' => $mois]);
    }

    #[Route('/releves-mvc/famille/{numFam}/signer', name: 'releves_famille_signer_mvc', methods: ['POST'])]
    public function signerReleveFamille(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->request->get('mois');
        $typePresta = $request->request->get('typePresta');
        
        // TODO: Implémenter le service de signature de relevé familial
        // $this->releveService->signerReleveFamille($numFam, $mois, $typePresta);
        
        $this->addFlash('success', 'Relevé signé avec succès');
        
        return $this->redirectToRoute('releves_famille_mvc', ['numFam' => $numFam, 'mois' => $mois]);
    }

    #[Route('/releves-mvc/intervenant/{intervenantId}/fiche', name: 'releves_intervenant_fiche_mvc')]
    public function ficheIntervenant(int $intervenantId, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->authService->isAdmin() && $this->authService->intervenant_id() !== $intervenantId) {
            return $this->redirectToRoute('dashboard');
        }

        $user = $this->intervenantService->getInfosIntervenant($intervenantId);
        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        $type = $request->query->get('type', 'ENFA');
        $moisOffset = (int)$request->query->get('mois', 0);
        $ua = $request->headers->get('User-Agent', '');
        $isMobile = (bool)preg_match('/Mobile|Android|iPhone|iPad/i', $ua);

        return $this->render('intervenants/hours/sheet.html.twig', [
            'auth' => $this->authService->check(),
            'user' => $user,
            'type' => $type,
            'moisOffset' => $moisOffset,
            'isMobile' => $isMobile,
            'isAdmin' => $this->authService->isAdmin(),
        ]);
    }

    #[Route('/releves-mvc/intervenant/{intervenantId}/km', name: 'releves_intervenant_km_mvc')]
    public function kmIntervenant(int $intervenantId, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->query->get('mois', date('m/Y'));
        
        // TODO: Implémenter le calcul des kilomètres pour un intervenant
        $kmTotal = 0;
        $detailsKm = [];

        return $this->render('releves/km.html.twig', [
            'auth' => $this->authService->check(),
            'intervenantId' => $intervenantId,
            'mois' => $mois,
            'kmTotal' => $kmTotal,
            'detailsKm' => $detailsKm,
        ]);
    }
}
