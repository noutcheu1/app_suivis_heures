<?php

namespace App\Tests\Service;

use App\Entity\Horaire\Conge;
use App\Repository\CongeRepository;
use App\Service\CongeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires du service de congés : validation des dates, anti-chevauchement,
 * statut initial (famille validée d'office / intervenant en attente) et verrou d'un
 * congé intervenant validé.
 */
#[CoversClass(CongeService::class)]
class CongeServiceTest extends TestCase
{
    private CongeRepository&MockObject $repo;
    private CongeService $service;

    protected function setUp(): void
    {
        $this->repo    = $this->createMock(CongeRepository::class);
        $this->service = new CongeService($this->repo);
    }

    // ── Statut initial ────────────────────────────────────────────────────────

    public function testAjouter_famille_estValideDoffice(): void
    {
        $this->repo->method('findChevauchant')->willReturn([]);
        $capture = null;
        $this->repo->method('save')->willReturnCallback(function (Conge $c) use (&$capture) { $capture = $c; });

        $res = $this->service->ajouter(Conge::PERSONNE_FAMILLE, 'M7690', '2026-08-01', '2026-08-10', null);

        $this->assertTrue($res['ok']);
        $this->assertSame(Conge::STATUT_VALIDE, $capture->getStatut());
        $this->assertSame(Conge::ORIGINE_LIBRE, $capture->getOrigine());
    }

    public function testAjouter_intervenant_estEnAttente(): void
    {
        $this->repo->method('findChevauchant')->willReturn([]);
        $capture = null;
        $this->repo->method('save')->willReturnCallback(function (Conge $c) use (&$capture) { $capture = $c; });

        $res = $this->service->ajouter(Conge::PERSONNE_INTERVENANT, '412', '2026-08-01', '2026-08-10', null);

        $this->assertTrue($res['ok']);
        $this->assertSame(Conge::STATUT_EN_ATTENTE, $capture->getStatut());
    }

    // ── Validation des dates ──────────────────────────────────────────────────

    public function testAjouter_datesManquantes_erreur(): void
    {
        $res = $this->service->ajouter(Conge::PERSONNE_FAMILLE, 'M1', '', '', null);

        $this->assertFalse($res['ok']);
        $this->assertNotNull($res['erreur']);
    }

    public function testAjouter_finAvantDebut_erreur(): void
    {
        $res = $this->service->ajouter(Conge::PERSONNE_FAMILLE, 'M1', '2026-08-10', '2026-08-01', null);

        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('fin', mb_strtolower($res['erreur']));
    }

    public function testAjouter_chevauchement_erreur(): void
    {
        // Un congé existant chevauche la période demandée.
        $this->repo->method('findChevauchant')->willReturn([new Conge()]);

        $res = $this->service->ajouter(Conge::PERSONNE_FAMILLE, 'M1', '2026-08-01', '2026-08-10', null);

        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('déjà', mb_strtolower($res['erreur']));
    }

    // ── Verrou d'un congé intervenant validé ──────────────────────────────────

    public function testModifier_intervenantValide_parIntervenant_estBloque(): void
    {
        $conge = (new Conge())
            ->setTypePersonne(Conge::PERSONNE_INTERVENANT)
            ->setStatut(Conge::STATUT_VALIDE);
        $this->repo->method('findUnActifPour')->willReturn($conge);

        $res = $this->service->modifier(5, Conge::PERSONNE_INTERVENANT, '412', '2026-08-01', '2026-08-05', null, [], false);

        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('validé', mb_strtolower($res['erreur']));
    }

    public function testModifier_intervenantValide_parAdmin_estAutorise(): void
    {
        $conge = (new Conge())
            ->setTypePersonne(Conge::PERSONNE_INTERVENANT)
            ->setStatut(Conge::STATUT_VALIDE)
            ->setPersonneId('412');
        $this->repo->method('findUnActifPour')->willReturn($conge);
        $this->repo->method('findChevauchant')->willReturn([]);

        $res = $this->service->modifier(5, Conge::PERSONNE_INTERVENANT, '412', '2026-08-01', '2026-08-05', null, [], true);

        $this->assertTrue($res['ok']);
    }

    public function testAnnuler_intervenantValide_parIntervenant_estRefuse(): void
    {
        $conge = (new Conge())
            ->setTypePersonne(Conge::PERSONNE_INTERVENANT)
            ->setStatut(Conge::STATUT_VALIDE);
        $this->repo->method('findUnActifPour')->willReturn($conge);

        $this->assertFalse($this->service->annuler(5, Conge::PERSONNE_INTERVENANT, '412', false));
    }

    public function testAnnuler_intervenantValide_parAdmin_estAutorise(): void
    {
        $conge = (new Conge())
            ->setTypePersonne(Conge::PERSONNE_INTERVENANT)
            ->setStatut(Conge::STATUT_VALIDE);
        $this->repo->method('findUnActifPour')->willReturn($conge);

        $this->assertTrue($this->service->annuler(5, Conge::PERSONNE_INTERVENANT, '412', true));
        $this->assertTrue($conge->estAnnule());
    }

    // ── Validation / refus / remise en attente (admin) ────────────────────────

    public function testValider_passeAValide(): void
    {
        $conge = (new Conge())->setStatut(Conge::STATUT_EN_ATTENTE);
        $this->repo->method('find')->with(9)->willReturn($conge);

        $this->assertTrue($this->service->valider(9));
        $this->assertSame(Conge::STATUT_VALIDE, $conge->getStatut());
    }

    public function testRemettreEnAttente_depuisValide(): void
    {
        $conge = (new Conge())->setStatut(Conge::STATUT_VALIDE);
        $this->repo->method('find')->with(9)->willReturn($conge);

        $this->assertTrue($this->service->remettreEnAttente(9));
        $this->assertSame(Conge::STATUT_EN_ATTENTE, $conge->getStatut());
    }

    public function testValider_congeIntrouvable_retourneFalse(): void
    {
        $this->repo->method('find')->willReturn(null);

        $this->assertFalse($this->service->valider(123));
    }
}
