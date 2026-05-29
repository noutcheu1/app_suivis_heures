<?php

namespace App\Controller;

use App\Repository\VacancesConfigRepository;
use App\Service\AuthService;
use App\Service\HoraireinterService;
use App\Service\IntervenantService;
use App\Service\ReleveMensuelFamilleService;
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
        private AuthService                 $authService,
        private HoraireinterService         $horaireService,
        private IntervenantService          $intervenantService,
        private VacancesConfigRepository    $vacancesRepo,
        private ReleveMensuelFamilleService $releveMensuelFamilleService,
    ) {}

    #[Route('/releves-mvc/intervenant/{intervenantId}', name: 'releves_intervenant_mvc')]
    public function relevesIntervenant(int $intervenantId, Request $request): Response
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

        $moisNoms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                     'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

        $rows      = $this->horaireService->getMoisDisponibles($intervenantId);
        $parMois   = [];
        foreach ($rows as $row) {
            $key = sprintf('%04d-%02d', $row['annee'], $row['mois']);
            if (!isset($parMois[$key])) {
                $parMois[$key] = [
                    'annee'  => (int)$row['annee'],
                    'mois'   => (int)$row['mois'],
                    'label'  => $moisNoms[(int)$row['mois']] . ' ' . $row['annee'],
                    'offset' => $this->moisOffset((int)$row['annee'], (int)$row['mois']),
                    'types'  => [],
                ];
            }
            $parMois[$key]['types'][] = $row['typePresta'];
        }

        return $this->render('releves/intervenant.html.twig', [
            'auth'          => $this->authService->check(),
            'intervenantId' => $intervenantId,
            'user'          => $user,
            'parMois'       => array_values($parMois),
            'isAdmin'       => $this->authService->isAdmin(),
        ]);
    }

    private function moisOffset(int $year, int $month): int
    {
        $now  = new \DateTime('first day of this month');
        $then = new \DateTime("$year-$month-01");
        $diff = (int)$now->diff($then)->days;
        $sign = $then < $now ? -1 : ($then > $now ? 1 : 0);
        return (int)round($sign * $diff / 30.44);
    }

    #[Route('/releves-mvc/famille/{numFam}', name: 'releves_famille_mvc')]
    public function relevesFamille(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->authService->isAdmin() && $this->authService->famille_id() !== $numFam) {
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
        if (!$this->authService->isAdmin() && $this->authService->intervenant_id() !== $intervenantId) {
            return $this->redirectToRoute('app_login');
        }

        $type       = strtoupper($request->query->get('type', 'ENFA'));
        $moisOffset = (int)$request->query->get('mois', 0);

        $user = $this->intervenantService->getInfosIntervenant($intervenantId);
        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        $releveData = $this->horaireService->getReleveData($intervenantId, $type, $moisOffset, $user);

        $joursFr = ['', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $moisFr  = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

        // Pre-compute rowspan per ISO week
        $rowspanMap = [];
        foreach ($releveData['jours'] as $j) {
            $d = new \DateTimeImmutable($j['date']);
            if ((int)$d->format('N') !== 7) {
                $w = (int)$d->format('W');
                $rowspanMap[$w] = ($rowspanMap[$w] ?? 0) + 1;
            }
        }

        $prevWeek = null;
        $jours    = [];
        foreach ($releveData['jours'] as $j) {
            $d     = new \DateTimeImmutable($j['date']);
            $dow   = (int)$d->format('N');
            $week  = (int)$d->format('W');
            $isDim = $dow === 7;
            $newW  = !$isDim && $week !== $prevWeek;
            if ($newW) $prevWeek = $week;

            $jours[] = [
                'date'            => $j['date'],
                'isDimanche'      => $isDim,
                'nouvelleSemaine' => $newW,
                'rowspan'         => $newW ? ($rowspanMap[$week] ?? 1) : 0,
                'numeroSemaine'   => $week,
                'nomJourCourt'    => $joursFr[$dow],
                'numeroJour'      => (int)$d->format('j'),
                'afficherMois'    => (int)$d->format('j') === 1,
                'nomMois'         => $moisFr[(int)$d->format('m')],
            ];
        }

        $fmt = static fn(int $sec): string => sprintf('%dh%02d', (int)($sec / 3600), (int)(($sec % 3600) / 60));

        $familles = array_map(static function (array $fam) use ($fmt): array {
            $fam['totalHeures'] = $fmt($fam['totalSecondes'] ?? 0);
            return $fam;
        }, $releveData['familles']);

        $totalSec    = array_sum(array_column($familles, 'totalSecondes'));
        $typeLabel   = $type === 'MENA' ? 'Menage' : 'GardeEnfants';
        $filename    = "Releve_{$typeLabel}_{$releveData['periode']['mois']}_{$releveData['periode']['anner']}.pdf";

        return $this->render('intervenants/hours/releve_pdf.html.twig', [
            'auth'          => $this->authService->check(),
            'intervenant'   => $releveData['intervenant'],
            'type'          => $type,
            'typeLibelle'   => $type === 'ENFA' ? "GARDES D'ENFANTS" : 'MÉNAGES',
            'periode'       => $releveData['periode'],
            'jours'         => $jours,
            'famillesPages' => array_chunk($familles, 5),
            'totaux'        => $releveData['totaux'],
            'totalHeures'   => $fmt($totalSec),
            'signer'        => $releveData['signer'],
            'afficherKm'    => $type === 'ENFA',
            'isAdmin'       => $this->authService->isAdmin(),
            'filename'      => $filename,
            'heureDehors'   => $releveData['heureDehors'] ?? null,
        ]);
    }

    #[Route('/releves-mvc/famille/{numFam}/pdf', name: 'releves_famille_pdf_mvc')]
    public function relevesFamillePdf(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->authService->isAdmin() && $this->authService->famille_id() !== $numFam) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->query->get('mois', date('m/Y'));
        $familles = $this->famillesservice->getFamilles($numFam);
        // TODO: Implémenter la génération PDF pour les relevés familles
        $pdfContent = "PDF Relevé Famille {$familles} - {$numFam} - {$mois}";
        
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', "attachment; filename=\"releve_famille_{$familles}_{$numFam}_{$mois}.pdf\"");
        
        return $response;
    }

    #[Route('/releves-mvc/intervenant/{intervenantId}/signer', name: 'releves_intervenant_signer_mvc', methods: ['POST'])]
    public function signerReleveIntervenant(int $intervenantId, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $moisOffset = (int)$request->request->get('mois', 0);
        $type       = strtoupper($request->request->get('typePresta', 'ENFA'));

        $date      = (new \DateTime('first day of this month'))->modify("$moisOffset month");
        $periodeFin = $date->format('Y-m-d');

        $result = $this->horaireService->signerReleve($intervenantId, $type, $periodeFin);

        if (!($result['success'] ?? false)) {
            $this->addFlash('error', $result['message'] ?? 'Impossible de signer ce relevé.');
        } else {
            $this->addFlash('success', 'Relevé signé avec succès.');
        }

        return $this->redirectToRoute('releves_intervenant_fiche_mvc', [
            'intervenantId' => $intervenantId,
            'type'          => $type,
            'mois'          => $moisOffset,
        ]);
    }

    #[Route('/releves-mvc/famille/{numFam}/signer', name: 'releves_famille_signer_mvc', methods: ['POST'])]
    public function signerReleveFamille(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->authService->isAdmin() && $this->authService->famille_id() !== $numFam) {
            return $this->redirectToRoute('app_login');
        }

        $moisOffset = (int)$request->request->get('mois', 0);
        $type       = strtoupper($request->request->get('typePresta', 'ENFA'));

        $date      = (new \DateTime('first day of this month'))->modify("$moisOffset month");
        $moisAnnee = $date->format('Y-m');

        $this->releveMensuelFamilleService->sauvegarderQuestionnaire(
            $numFam,
            $moisAnnee,
            [$type],
            ['signer' => '1']
        );

        $this->addFlash('success', 'Relevé signé avec succès.');

        return $this->redirectToRoute('releves_famille_mvc', ['numFam' => $numFam]);
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
            'auth'           => $this->authService->check(),
            'user'           => $user,
            'type'           => $type,
            'moisOffset'     => $moisOffset,
            'isMobile'       => $isMobile,
            'isAdmin'        => $this->authService->isAdmin(),
            'vacancesConfig' => $this->vacancesRepo->findActif(),
        ]);
    }

    #[Route('/releves-mvc/intervenant/{intervenantId}/km', name: 'releves_intervenant_km_mvc')]
    public function kmIntervenant(int $intervenantId, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->authService->isAdmin() && $this->authService->intervenant_id() !== $intervenantId) {
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
