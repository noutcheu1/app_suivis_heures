<?php

namespace App\Controller;

use App\PdoApp;
use App\Security\Auth;
use App\Service\DateReleveService;
use App\Service\FeuilleHeuresService;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Detection\MobileDetect;
use Twig\Node\Expression\Test\DefinedTest;

final class IntervenantsController extends AbstractController
{
    #[Route('/intervenants', name: 'intervenants')]
    public function intervenants(PdoApp $pdo, Request $request): Response
    {
        $users = $pdo->getTousLesIntervenants();

        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin()) {
            return $this->redirectToRoute('intervenant_panel', [
                'id' => $auth->intervenant_id()
            ]);
        }

        return $this->render('intervenants/index.html.twig', [
            'auth' => $auth->check(),
            'users' => $users,
        ]);
    }

    #[Route('/intervenants/{id}', name: 'intervenant_switch_panel')]
    public function intervenant_redirect_panel(int $id): Response
    {
        return $this->redirectToRoute('intervenant_panel', [
            'id' => $id
        ]);
    }

    #[Route('/intervenants/{id}/panel', name: 'intervenant_panel')]
    public function intervenant_panel(int $id, Request $request, PdoApp $pdo): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $id != $auth->intervenant_id()) {
            return $this->redirectToRoute('intervenant_panel', [
                'id' => $auth->intervenant_id()
            ]);
        }

        $user = $pdo->getInfosIntervenant($id);
        
        $auth = new Auth($request->getSession());

        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        return $this->render('intervenants/panel.html.twig', [
            'auth' => $auth->check(),
            'user' => $user,
        ]);
    }

    #[Route('/intervenants/{id}/profile', name: 'intervenant_profile')]
    public function intervenant_profile(int $id, Request $request, PdoApp $pdo): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $id != $auth->intervenant_id()) {
            return $this->redirectToRoute('intervenant_profile', [
                'id' => $auth->intervenant_id()
            ]);
        }


        $user = $pdo->getInfosIntervenant($id);
        $auth = new Auth($request->getSession());

        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }


        return $this->render('intervenants/profile.html.twig', [
            'auth' => $auth->check(),
            'user' => $user,
        ]);
    }

    #[Route('/intervenants/{id}/heures', name: 'intervenant_heures')]
    public function heures(int $id, Request $request, PdoApp $pdo): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $id != $auth->intervenant_id()) {
            return $this->redirectToRoute('intervenant_heures', [
                'id' => $auth->intervenant_id()
            ]);
        }


        $recupId = $request->query->get('recup');
        $suppId  = $request->query->get('supp');

        $auth = new Auth($request->getSession());

        if ($recupId) {
            $userId = $pdo->putRecupererHoraire($recupId);
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('les_heures', ['id' => $userId]));
        }

        if ($suppId) {
            $userId = $pdo->putArchiverHoraire($suppId);
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('les_heures', ['id' => $userId]));
        }
        if ($auth->isAdmin()) {
            $user = $pdo->getIntervenantNumInter($id);
        } else {
            $user = $pdo->getIntervenantNumInter($auth->intervenant_id());
        }
        
        $prestations = $pdo->getToutePresations($id);

        $user["id"] = $id;
        return $this->render('intervenants/heures.html.twig', [
            'auth' => $auth->check(),
            'user' => $user,
            'prestations' => $prestations,
        ]);
    }

    #[Route('/intervenants/{id}/suivie', name: 'intervenant_suivie')]
    public function suivie(int $id, Request $request, PdoApp $pdo): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $id != $auth->intervenant_id()) {
            return $this->redirectToRoute('intervenant_suivie', [
                'id' => $auth->intervenant_id()
            ]);
        }

        $session = $request->getSession();

        $auth = new Auth($request->getSession());

        $user = $pdo->getIntervenantNumInter($id);
        $user["id"] = $id;
        $prestations = $pdo->getToutePresations($id);

        $familles = $pdo->getFamillesIntervenantNumInter($id);

        // config JSON
        $nbrJourSaisie = null;
        if (!$auth->isAdmin()) {
            $config = json_decode(
                file_get_contents($this->getParameter('kernel.project_dir').'/configuration.json'),
                true
            );
            $nbrJourSaisie = $config['nbrJourSaisie'];
        }
    
        return $this->render('intervenants/suivie.html.twig', [
            'auth' => $auth->check(),
            'isAdmin' => $auth->isAdmin(),
            'familles' => $familles,
            'nbrJourSaisie' => $nbrJourSaisie,
            'user' => $user,
            'prestations' => $prestations,
            'editId' => $request->query->has('edite')
        ]);
    }

    #[Route('/api/intervenants/horaire/ajouter/{id}', name: 'api_intervenant_sauvegarder', methods: ['POST'])]
    public function api_ajout_horaire(
        Request $request,
        PdoApp $pdo,
        int $id
    ): JsonResponse {
        
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $auth->intervenant_id() == $id) {
            $this->json([
                'success'  => false,
                'message' => 'Accer non autoriser veulier vous connecter',
                'redirect' => null,
            ], 401);
        }

        // TODO Ajouter de la secu

        $data = json_decode($request->getContent(), true);

        $idInter = $id;
        $famille = $data['famille'] ?? null;
        $nomRemplacement = $data['nomRemplacement'] ?? "";
        $type = $data['type'] ?? null;
        $date = $data['date'] ?? null;
        $hDebut = $data['heureDebut'] . ':' . $data['minuteDebut'];
        $hFin = $data['heureFin'] . ':' . $data['minuteFin'];
        $trajet = (float)($data['trajet'] ?? 0);


        $date1 = new DateTime($hDebut);
        $date2 = new DateTime($hFin);

        /* ------------- CALCUL de la diff de temps ------------ */
        if ($date1->getTimestamp() > $date2->getTimestamp()) {
            $date2 = $date2->modify('+1 day');
        }

        $diffSeconds = abs($date1->getTimestamp() - $date2->getTimestamp());

        if ($hDebut === $hFin) {
            return $this->json([
                'success' => false,
                'message' => 'Veuliez entrer des heures correct SVP']);
        }

        if ($diffSeconds > 10 * 3600) {
            return $this->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas faire plus de 10h dans une journée']);
        }

        // Cas particulier pour fin à 00:00
        if ($hFin === "0:0") $hFin = "24:00";

        try {
            $isChevaucher = $pdo->getChevauchementHoraireInter($id, $date, $hDebut, $hFin );

            if ($isChevaucher) {
                return $this->json([
                    'success' => false,
                    'message' => 'Horraire incorect elle chevauche des horaraies existantes. Veulier les modifier ']);
                }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur serveur : '. $e->getMessage()]);
        } 

        

        try {
            // Découpage sur 2 jours si nécessaire
            $success = false;
            if ($data['heureDebut'] > $data['heureFin']) {
                $date2 = (new \DateTime($date))->modify('+1 day')->format('Y-m-d');
                $success = $pdo->postHoraireInter($famille ?? 9998, $nomRemplacement, $idInter, $date, $hDebut, "24:00", $type, $trajet);
                $success = $pdo->postHoraireInter($famille ?? 9998, $nomRemplacement, $idInter, $date2, "00:00", $hFin, $type, $trajet);
            } else {
                $success = $pdo->postHoraireInter($famille ?? 9998, $nomRemplacement, $idInter, $date, $hDebut, $hFin, $type, $trajet);
            }

            return $this->json([
                'success' => $success
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur serveur : '. $e->getMessage()]);
        }

    }

    #[Route('/api/intervenants/horaire/modifier/{id}', name: 'api_intervenant_modifier', methods: ['POST'])]
    public function api_modifie_horaire(
        Request $request,
        PdoApp $pdo,
        int $id
    ): JsonResponse {
        
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $auth->intervenant_id() != $id) {
            return $this->json([
                'success'  => false,
                'message' => 'Accer non autoriser veulier vous connecter',
                'redirect' => null,
            ], 401);
        }        

        $data = json_decode($request->getContent(), true);

        if (!isset($data['id'])) {
            return $this->json([
                'success'  => false,
                'message' => 'ID d\'edtion manquante',
                'redirect' => null,
            ], 401);
        }

        $IDFiche = $data['id'] ?? null;
        $idInter = $id;
        $famille = $data['famille'] ?? null;
        $nomRemplacement = $data['nomRemplacement'] ?? null;
        $type = $data['type'] ?? null;
        $date = $data['date'] ?? null;
        $hDebut = $data['heureDebut'] . ':' . $data['minuteDebut'];
        $hFin = $data['heureFin'] . ':' . $data['minuteFin'];
        $trajet = (float)($data['trajet'] ?? 0);

        // Cas particulier pour fin à 00:00
        if ($hFin === "0:0") $hFin = "24:00";

        try {
            $isChevaucher = $pdo->getChevauchementHoraireInterExludeID(
                $IDFiche, $idInter, $date, $hDebut, $hFin
            );

            if ($isChevaucher) {
                return $this->json([
                    'success' => false,
                    'message' => 'Horraire incorect elle chevauche des horaraies existantes. Veulier les modifier ']);
            }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur serveur : '. $e->getMessage()]);
        } 

        try {
            // Découpage sur 2 jours si nécessaire
            if ($data['heureDebut'] > $data['heureFin']) {
                $date2 = (new \DateTime($date))->modify('+1 day')->format('Y-m-d');
                $pdo->putHoraireInter(
                    $famille ?? 9998, 
                    $nomRemplacement, 
                    $idInter, 
                    $date, 
                    $hDebut, 
                    "24:00", 
                    $type, 
                    $trajet, 
                    $IDFiche
                );
                $pdo->putHoraireInter(
                    $famille ?? 9998, 
                    $nomRemplacement, 
                    $idInter, 
                    $date2, 
                    "00:00", 
                    $hFin, 
                    $type, 
                    $trajet, 
                    $IDFiche
                );
            } else {
                $pdo->putHoraireInter(
                    $famille ?? 9998, 
                    $nomRemplacement, 
                    $idInter, 
                    $date, 
                    $hDebut, 
                    $hFin, 
                    $type, 
                    $trajet, 
                    $IDFiche
                );
            }

            return $this->json(['success' => true]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur serveur : '.$e->getMessage()]);
        }

    }

    #[Route('/api/intervenants/horaire/{id}', name: 'api_intervenant_get_modifier', methods: ['GET'])]
    public function api_get_modifier_horaire(
        Request $request,
        PdoApp $pdo,
        int $id
    ): JsonResponse {
        
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $auth->intervenant_id() == $id) {
            $this->json([
                'success'  => false,
                'message' => 'Accer non autoriser veulier vous connecter',
            ], 401);
        }

        if ($request->query->has('id_edit')) {
            $id_edit = $request->query->get('id_edit');
            // TODO Amméliorer la sécurité
            $data = $pdo->getSaisieHeure($id_edit);
            if (!$data) {
                return $this->json([
                    'success'  => false,
                    'message' => 'Aucune donnée editable trouver',
                ], 401);
            }
        } else {
            $this->json([
                'success'  => false,
                'message' => 'id_edit incorect',
            ], 401);
        }

        return $this->json([
            'success'  => true,
            'data' => $data,
            'redirect' => null,
        ]);
    }

    #[Route('/api/intervenants/releve/{id}', name: 'api_intervenant_releve')]
    public function api_relever_fiche(
        int $id, 
        Request $request, 
        PdoApp $pdo, 
        DateReleveService $dateService
    ): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $auth->intervenant_id() == $id) {
            $this->json([
                'success'  => false,
                'message' => 'Accer non autoriser veulier vous connecter',
            ], 401);
        }

        $type = $request->query->get('type');
        $moisOffset = (int) $request->query->get('mois', 0);

        if (!in_array($type, ['ENFA', 'MENA'])) {
            return $this->json(['error' => 'Type invalide'], 400);
        }

        $numInter = $id;


        [$debut, $fin, $jours] = $dateService->buildPeriode($type, $moisOffset);

        // Familles et menage sont sur la même table
        $dataFamilles = $pdo->getFamilles($debut, $fin, $type, $numInter);
        $prestations  = $pdo->getPresationsInter($debut, $fin, $numInter, $type );

        // Ne sert plus à rien garder par précaution. Pour la base alogrithmique
        foreach ($dataFamilles as &$fam) {
            $fam['prestations'] = [];
            $fam['totalSecondes'] = 0;
            $fam['totalKm'] = 0;

            foreach ($prestations as $p) {
                if ($p['nomFam'] !== $fam['nomFam']) continue;
                
                [$h, $m, $s] = explode(':', $p['durees']);
                $sec = (int)$h * 3600 + (int)$m * 60 + (int)$s;

                $fam['prestations'][$p['datePresta']][] =
                    (int)$h . 'h' . str_pad($m, 2, '0', STR_PAD_LEFT);

                $fam['totalSecondes'] += $sec;
                $fam['totalKm'] += (float) ($p['kmAvecEnfant'] ?? 0);
            }
        }
        unset($fam);

        $releve = $pdo->getReleverMensuelInter($type, $fin->format('Y-m'), $numInter);
        if (!$releve) {
            $pdo->postReleverMensuelInter($type,  $fin->format('Y-m'), $numInter);
            $releve = $pdo->getReleverMensuelInter($type, $fin->format('Y-m'), $numInter);
        }
        
        $totaux =  $dateService->getTotaux($dataFamilles, $releve);
        $user = $pdo->getInfosIntervenant($id);
        

        return $this->json([
            'type' => $type,
            'periode' => [
                'debut' => $debut->format('Y-m'),
                'fin' => $fin->format('Y-m'),
                'anner' => $fin->format('Y'),
                'mois' => FeuilleHeuresService::getMoisByIndex((int)$fin->format('m'))
            ],
            'jours' => $jours,
            'intervenant' => $user,
            'familles' => $dataFamilles,
            'signer' => [
                'etat' => (bool) ($releve['signer'] ?? false),
                'date' => $releve['signerLe'] ?? null,
                'nom' => $user['nom'],
            ],
            'totaux' => $totaux,
        ]);
    }

    #[Route('/api/intervenants/{id}/ajout_heure_de_hors', name: 'api_ajout_heure_de_hors', methods: ['POST'])]
    public function api_ajout_heure_de_hors(int $id, Request $request, PdoApp $pdo): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $auth->intervenant_id() != $id) {
            return $this->json([
                'success'  => false,
                'message' => 'Accer non autoriser veulier vous connecter',
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        $type = $data['type'] ?? null;
        $heure = $data['heure'] ?? null;
        $minute = $data['minute'] ?? null;
        $periodeFin = $data['periodeFin'] ?? null;


        if (!$type) {
            return $this->json([
                'success'  => false,
                'message' => 'Type incorect',
            ], 400);
        }

        if (!$periodeFin) {
            return $this->json([
                'success'  => false,
                'message' => 'Periode de la fiche invalide',
            ], 400);
        }

        if (!$heure || !$minute) {
            return $this->json([
                'success'  => false,
                'message' => 'Heure incorecte',
            ], 400);
        }
        
        $oki = $pdo->putHeureDehors($periodeFin, $type, $id, $heure.":".$minute);

        if (!$oki) {
            return $this->json([
                'success'  => false,
                'message' => 'Une erreur inconue est survenue veullier réessayer ultérieurement',
            ], 401);
        };
        

        return $this->json([
                'success'  => true,
                'message' => 'Nombre d\'heure à bien étais enregistrer',
        ], 200);
    }

    #[Route('/api/intervenants/{id}/signer', name: 'api_signer')]
    public function api_fiche_signer(int $id, Request $request, PdoApp $pdo): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $auth->intervenant_id() != $id) {
            return $this->json([
                'success'  => false,
                'message' => 'Accer non autoriser veulier vous connecter',
            ], 401);
        }

        $type = $request->query->get('type');

        if (!in_array($type, ['ENFA', 'MENA'])) {
            return $this->json([
                'success'  => false,
                'message' => 'Type incorect',
            ], 400);
        }

        $periodeFin = $request->query->get('periode_fin');

        if (!isset($periodeFin)) {
            return $this->json([
                'success'  => false,
                'message' => 'Periode de la fiche invalide',
            ], 400);
        }
        
        $oki = $pdo->putSignerInter($periodeFin, $type, $id);

        if (!$oki) {
            return $this->json([
                'success'  => false,
                'message' => 'Une erreur inconue est survenue veullier réessayer ultérieurement',
            ], 401);
        };
        

        return $this->json([
                'success'  => true,
                'message' => 'Document signer',
                'oki' => $oki
        ], 200);
    }

    #[Route('/intervenants/{id}/fiches', name: 'intervenant_fiches')]
    public function admin_fiches(int $id, Request $request, PdoApp $pdo): Response
    {
        $user = $pdo->getInfosIntervenant($id);
        $auth = new Auth($request->getSession());

        $detect = new MobileDetect();

        $type = $request->query->get('type');
        $moisOffset = (int)$request->query->get('mois', 0);

        if ($moisOffset >= 0 ) {
            // Redirgier idealement l'url
            $moisOffset = 0;
        }

        if (!in_array($type, ['ENFA', 'MENA'])) {
            throw $this->createNotFoundException();
        }

        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        return $this->render('intervenants/fiche-heure.html.twig', [
            'auth' => $auth->check(),
            'isAdmin' => $auth->isAdmin(),
            'user' => $user,
            'isMobile' => $detect->isMobile(),
            'moisOffset' => $moisOffset,
            'type' => $type
        ]);
    }
}
