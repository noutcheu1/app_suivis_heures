<?php

namespace App\Tests\Service;

use App\Entity\Famille;
use App\Repository\FamilleRepository;
use App\Repository\HoraireinterRepository;
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
    private FamilleRepository&MockObject $familleRepo;
    private HoraireinterRepository&MockObject $horaireRepo;
    private EntityManagerInterface&MockObject $em;
    private FamilleService $service;

    protected function setUp(): void
    {
        $this->familleRepo = $this->createMock(FamilleRepository::class);
        $this->horaireRepo = $this->createMock(HoraireinterRepository::class);
        $this->em          = $this->createMock(EntityManagerInterface::class);

        $this->service = new FamilleService(
            $this->familleRepo,
            $this->em,
            $this->horaireRepo
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Fonctionnalité 3 — Liste des familles dans le planning
    // ──────────────────────────────────────────────────────────────────────────

    public function testGetFamillesDeIntervenant_returnsEmptyArray_whenNoFamilies(): void
    {
        $this->horaireRepo->method('findDistinctFamilleNumsByIntervenant')->with(99)->willReturn([]);

        $result = $this->service->getFamillesDeIntervenant(99);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetFamillesDeIntervenant_returnsFamilies_forAssignedIntervenant(): void
    {
        $famille1 = $this->buildFamille('FAM001', 'Dupont');
        $famille2 = $this->buildFamille('FAM002', 'Martin');

        $this->horaireRepo
            ->method('findDistinctFamilleNumsByIntervenant')
            ->with(5)
            ->willReturn(['FAM001', 'FAM002']);

        $this->familleRepo
            ->method('findByNumero')
            ->willReturnMap([
                ['FAM001', $famille1],
                ['FAM002', $famille2],
            ]);

        $result = $this->service->getFamillesDeIntervenant(5);

        $this->assertCount(2, $result);
        $this->assertSame($famille1, $result[0]);
        $this->assertSame($famille2, $result[1]);
    }

    public function testGetFamillesDeIntervenant_skipsUnknownFamilyNums(): void
    {
        $famille1 = $this->buildFamille('FAM001', 'Dupont');

        $this->horaireRepo
            ->method('findDistinctFamilleNumsByIntervenant')
            ->willReturn(['FAM001', 'FAM_INCONNUE']);

        $this->familleRepo
            ->method('findByNumero')
            ->willReturnMap([
                ['FAM001', $famille1],
                ['FAM_INCONNUE', null],
            ]);

        $result = $this->service->getFamillesDeIntervenant(3);

        $this->assertCount(1, $result);
        $this->assertSame($famille1, $result[0]);
    }

    public function testGetFamillesDeIntervenant_queriesRepositoryWithCorrectId(): void
    {
        $this->horaireRepo
            ->expects($this->once())
            ->method('findDistinctFamilleNumsByIntervenant')
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
