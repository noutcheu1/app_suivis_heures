<?php

namespace App\Controller;

use App\Repository\VacancesConfigRepository;
use App\Repository\VacancesReponseFamilleRepository;
use App\Repository\VacancesReponseIntervenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Formulaires de réponse à une campagne de congés, accessibles via un lien à JETON
 * (magic link, sans connexion). Un jeton = une réponse (famille ou intervenant).
 */
final class VacancesReponseController extends AbstractController
{
    public function __construct(
        private VacancesConfigRepository             $configRepo,
        private VacancesReponseFamilleRepository     $reponseFamRepo,
        private VacancesReponseIntervenantRepository $reponseInterRepo,
        private EntityManagerInterface               $em,
        private \App\Repository\IntervenantDispoRepository $dispoRepo,
    ) {}

    // ── Famille ──────────────────────────────────────────────────────────────
    #[Route('/vacances/famille/{token}', name: 'vacances_reponse_famille', methods: ['GET', 'POST'])]
    public function famille(string $token, Request $request): Response
    {
        $reponse = $this->reponseFamRepo->findByToken($token);
        if (!$reponse) {
            return $this->render('vacances/lien_invalide.html.twig', [], new Response('', 404));
        }
        $config = $this->configRepo->find($reponse->getVacancesConfigId());

        // Période de réponse terminée → formulaire fermé (même pour un POST).
        if ($this->periodeTerminee($config)) {
            return $this->render('vacances/ferme.html.twig', ['config' => $config, 'reponse' => $reponse]);
        }

        if ($request->isMethod('POST')) {
            // Formulaire basique : maintient-elle les prestations ménage ? (oui/non)
            // « non » → la famille indique la période sans ménage (= dates d'absence).
            $maintien  = $request->request->get('maintien'); // 'oui' | 'non' | null
            $maintientPrestation = $maintien === null ? null : $maintien === 'oui';
            $avecDates = $maintien !== 'non'; // dates lues sauf si « Non »
            $debut     = $avecDates ? $this->date($request->request->get('dateDebut')) : null;
            $fin       = $avecDates ? $this->date($request->request->get('dateFin')) : null;

            // Les dates doivent rester DANS la période de la campagne.
            $erreur = $avecDates ? $this->validerDates($debut, $fin, $config) : null;
            if ($erreur) {
                return $this->render('vacances/reponse_famille.html.twig', [
                    'config' => $config, 'reponse' => $reponse, 'erreur' => $erreur,
                ]);
            }

            // Souhait de remplacement pendant les congés de l'intervenante (oui/non).
            $rempl = $request->request->get('souhaiteRemplacement');
            $souhaiteRemplacement = $rempl === null ? null : $rempl === 'oui';

            // Situation dérivée pour l'affichage admin : maintien non = créneaux suspendus.
            $reponse->setSituation($maintien === 'non' ? 'absence' : 'aucune');
            $reponse->setDateDebutAbsence($debut);
            $reponse->setDateFinAbsence($fin);
            $reponse->setMaintienPrestation($maintientPrestation);
            $reponse->setSouhaiteRemplacement($souhaiteRemplacement);
            $reponse->setCommentaire(trim((string) $request->request->get('commentaire')) ?: null);
            $reponse->setRepondu(true);
            $reponse->setReponduLe(new \DateTimeImmutable());
            $this->em->flush();

            return $this->render('vacances/merci.html.twig', ['config' => $config]);
        }

        return $this->render('vacances/reponse_famille.html.twig', [
            'config'  => $config,
            'reponse' => $reponse,
        ]);
    }

    // ── Intervenant ──────────────────────────────────────────────────────────
    #[Route('/vacances/intervenant/{token}', name: 'vacances_reponse_intervenant', methods: ['GET', 'POST'])]
    public function intervenant(string $token, Request $request): Response
    {
        $reponse = $this->reponseInterRepo->findByToken($token);
        if (!$reponse) {
            return $this->render('vacances/lien_invalide.html.twig', [], new Response('', 404));
        }
        $config = $this->configRepo->find($reponse->getVacancesConfigId());

        // Période de réponse terminée → formulaire fermé (même pour un POST).
        if ($this->periodeTerminee($config)) {
            return $this->render('vacances/ferme.html.twig', ['config' => $config, 'reponse' => $reponse]);
        }

        if ($request->isMethod('POST')) {
            $enConge = $request->request->get('enConge') === 'oui';
            $debut   = $enConge ? $this->date($request->request->get('dateDebut')) : null;
            $fin     = $enConge ? $this->date($request->request->get('dateFin')) : null;

            // Les dates de congé doivent rester DANS la période de la campagne.
            $erreur = $enConge ? $this->validerDates($debut, $fin, $config) : null;
            if ($erreur) {
                return $this->render('vacances/reponse_intervenant.html.twig', [
                    'config' => $config, 'reponse' => $reponse, 'erreur' => $erreur,
                ]);
            }

            $reponse->setDateDebutConge($debut);
            $reponse->setDateFinConge($fin);
            // Disponibilité aux remplacements : demandée dans TOUS les cas (qu'il prenne
            // des congés ou non), car elle vaut « en dehors de ses congés ».
            $dispo = $request->request->get('dispo') === 'oui';
            $reponse->setDisponibleRemplacement($dispo);
            $reponse->setCommentaire(trim((string) $request->request->get('commentaire')) ?: null);
            $reponse->setRepondu(true);
            $reponse->setReponduLe(new \DateTimeImmutable());
            $this->em->flush();

            // Convergence : la campagne alimente aussi la dispo GÉNÉRALE (source de
            // vérité utilisée par le matching des remplacements).
            $this->dispoRepo->definir($reponse->getNumInter(), $dispo);

            return $this->render('vacances/merci.html.twig', ['config' => $config]);
        }

        return $this->render('vacances/reponse_intervenant.html.twig', [
            'config'  => $config,
            'reponse' => $reponse,
        ]);
    }

    /** La date limite de réponse est-elle dépassée ? (réponses closes) */
    private function periodeTerminee(?object $config): bool
    {
        $limite = $config?->getDateLimiteReponse();
        return $limite && new \DateTimeImmutable('today') > $limite;
    }

    /** Vérifie que les dates saisies sont valides ET dans la période de la campagne. */
    private function validerDates(?\DateTimeImmutable $debut, ?\DateTimeImmutable $fin, ?object $config): ?string
    {
        if (!$debut || !$fin) {
            return 'Merci d\'indiquer vos deux dates.';
        }
        if ($debut > $fin) {
            return 'La date de fin doit être après la date de début.';
        }
        if ($config && $config->getDateDebut() && $config->getDateFin()
            && ($debut < $config->getDateDebut() || $fin > $config->getDateFin())) {
            return 'Vos dates doivent être comprises dans la période concernée : du '
                . $this->dateFr($config->getDateDebut()) . ' au ' . $this->dateFr($config->getDateFin()) . '.';
        }
        return null;
    }

    /** « 24 septembre 2026 » (mois français, sans dépendre de la locale). */
    private function dateFr(\DateTimeInterface $d): string
    {
        $mois = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        return (int) $d->format('j') . ' ' . $mois[(int) $d->format('n')] . ' ' . $d->format('Y');
    }

    private function date(?string $s): ?\DateTimeImmutable
    {
        $s = trim((string) $s);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return null;
        }
        try { return new \DateTimeImmutable($s); } catch (\Exception) { return null; }
    }
}
