<?php

namespace App\Controller;

use App\Security\Auth;
use App\Service\FeuilleHeuresService;
use App\PdoApp;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/dashboard/recapitulatif_intervenant', name: 'recap_intervenant')]
    public function recap_intervenant(Request $request, PdoApp $pdo): Response
    {
        $auth = new Auth($request->getSession());
        $moisOffset = (int) $request->query->get('mois', 0);

        if ( $moisOffset > 0 ) {
            return $this->redirectToRoute('recap_intervenant', [
                'mois' => 0
            ]);
        }

        $date = new \DateTime('now');
        $date->modify($moisOffset . ' month');

        $intervenants = $pdo->getTouteIntervenantsPrestations($date);

        return $this->render('admin/recapitulatif_intervenant.html.twig', [
            'auth' => $auth->check(),
            'intervenants' => $intervenants,
            'moisLabel' => FeuilleHeuresService::getMoisByIndex((int)$date->format('n')),
            'annee' => $date->format('Y'),
            'moisOffset' => $moisOffset,
        ]);
    }

    #[Route('/dashboard/fiche_vierge', name: 'fiche_vierge')]
    public function fiche_vierge(Request $request): Response
    {

        $auth = new Auth($request->getSession());
        return $this->render('admin/fiche_vierge.html.twig', [
            'controller_name' => 'AdminController',
            'auth' => $auth->check()
        ]);
    }

    #[Route('/dashboard/fiche_vierge/build', name: 'fiche_vierge_build')]
    public function fiche_vierge_build(
        Request $request,
        FeuilleHeuresService $feuilleHeuresService
    ): Response
    {
        $auth = new Auth($request->getSession());
        $feuilleHeuresService = new FeuilleHeuresService();

        $anneeDebut = (int) $request->request->get('anneeDebut');
        $moisDebut = (int) $request->request->get('moisDebut');
        $anneeFin = (int) $request->request->get('anneeFin');
        $moisFin = (int) $request->request->get('moisFin');
        $type = $request->request->get('type');

        // Validation basique
        if (!in_array($type, ['MENA', 'ENFA'])) {
            $this->addFlash('error', 'Type de garde invalide');
            return $this->redirectToRoute('fiche_vierge');
        }

        $fiches = $feuilleHeuresService->genererFiches(
            $anneeDebut,
            $moisDebut,
            $anneeFin,
            $moisFin,
            $type
        );

        return $this->render('admin/fiche_vierge_generer.html.twig', [
            'auth' => $auth->check(),
            'fiches' => $fiches,
            'type' => $type,
            'typeLibelle' => $type === 'MENA' ? 'MENAGES ' : 'GARDES D\'ENFANTS ',
            'anneeFile' => $feuilleHeuresService->genererNomFichier(
                $moisDebut,
                $anneeDebut,
                $moisFin,
                $anneeFin
            )
        ]);
    }

    #[Route('/dashboard/famille/retour', name: 'admin_retour_famille')]
    public function famille_profile(Request $request, PdoApp $pdo): Response
    {
        $auth = new Auth($request->getSession());

        $ancien = $request->query->get("ancien", "0") ;

        if ($ancien === "1") {
            $ancien = true;
        } else {
            $ancien = false;
        }

        $prestation = $pdo->getPresationsSignaler( (int)$ancien );
        
        return $this->render('admin/retour_famille.html.twig', [
            'auth' => $auth->check(),
            'ancien' => $ancien,
            'prestation' => $prestation
        ]);
    }

    #[Route('/dashboard/prepare_paie', name: 'admin_prepare_paie')]
    public function prepare_paie(Request $request, PdoApp $pdo): Response
    {
        $auth = new Auth($request->getSession());

        $mois = $request->query->get("mois", "0");

        $date = new DateTime("now");
        $date->modify($mois . " month");
        $intervenants = $pdo->preparationPaie($date);

        $moisNom = FeuilleHeuresService::getMoisByIndex((int)date('n', strtotime($mois.' month'))) . " " . date('Y', strtotime($mois . ' month'));
        
        return $this->render('admin/prepare_paie.html.twig', [
            'auth' => $auth->check(),
            'intervenants' => $intervenants,
            'moisNum' => $mois,
            'moisNom' => $moisNom
        ]);
    }
}
