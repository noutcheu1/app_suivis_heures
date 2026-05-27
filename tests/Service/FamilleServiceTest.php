<?php

namespace App\Tests\Service;

use App\Entity\Principal\Famille;
use App\Repository\EnfantRepository;
use App\Repository\FamilleRepository;
use App\Service\FactureService;
use App\Service\FamilleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour FamilleService.
 * Couvre la fonctionnalité 3 — liste des familles dans le planning d'un intervenant.
 */
#[CoversClass(FamilleService::class)]
class FamilleServiceTest extends TestCase
{
    private FamilleRepository&MockObject  $familleRepo;
    private EnfantRepository&MockObject   $enfantRepo;
    private EntityManagerInterface&MockObject $em;
    private FactureService&MockObject     $factureService;
    private FamilleService $service;

    protected function setUp(): void
    {
        $this->familleRepo    = $this->createMock(FamilleRepository::class);
        $this->enfantRepo     = $this->createMock(EnfantRepository::class);
        $this->em             = $this->createMock(EntityManagerInterface::class);
        $this->factureService = $this->createMock(FactureService::class);

        $this->service = new FamilleService(
            $this->familleRepo,
            $this->enfantRepo,
            $this->em,
            $this->factureService,
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Fonctionnalité 3 — Liste des familles dans le planning
    // ──────────────────────────────────────────────────────────────────────────

    public function testGetFamillesDeIntervenant_returnsEmptyArray_whenNoFamilies(): void
    {
        $this->familleRepo->method('findByIntervenantActif')->with(99)->willReturn([]);

        $result = $this->service->getFamillesDeIntervenant(99);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetFamillesDeIntervenant_returnsFamilies_forAssignedIntervenant(): void
    {
        $famille1 = $this->buildFamille('FAM001', 'Dupont');
        $famille2 = $this->buildFamille('FAM002', 'Martin');

        $this->familleRepo
            ->method('findByIntervenantActif')
            ->with(5)
            ->willReturn([$famille1, $famille2]);

        $result = $this->service->getFamillesDeIntervenant(5);

        $this->assertCount(2, $result);
        $this->assertSame($famille1, $result[0]);
        $this->assertSame($famille2, $result[1]);
    }

    public function testGetFamillesDeIntervenant_returnsOnlyMatchingFamilies(): void
    {
        $famille1 = $this->buildFamille('FAM001', 'Dupont');

        $this->familleRepo
            ->method('findByIntervenantActif')
            ->willReturn([$famille1]);

        $result = $this->service->getFamillesDeIntervenant(3);

        $this->assertCount(1, $result);
        $this->assertSame($famille1, $result[0]);
    }

    public function testGetFamillesDeIntervenant_queriesRepositoryWithCorrectId(): void
    {
        $this->familleRepo
            ->expects($this->once())
            ->method('findByIntervenantActif')
            ->with(42)
            ->willReturn([]);

        $this->service->getFamillesDeIntervenant(42);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helper
    // ──────────────────────────────────────────────────────────────────────────

    private function buildFamille(string $numero, string $nom): Famille
    {
        $f = new Famille();
        $f->setNumeroFamille($numero);
        $f->setNomFamille($nom);
        return $f;
    }
}
