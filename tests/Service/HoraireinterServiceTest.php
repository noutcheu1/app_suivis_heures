<?php

namespace App\Tests\Service;

use App\Entity\Horaire\AppConfig;
use App\Entity\Horaire\Horaireinter;
use App\Repository\AppConfigRepository;
use App\Repository\FamilleRepository;
use App\Repository\HoraireinterRepository;
use App\Repository\ProposerRepository;
use App\Repository\RelevemensuelinterRepository;
use App\Service\HoraireinterService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(HoraireinterService::class)]
class HoraireinterServiceTest extends TestCase
{
    private HoraireinterRepository&MockObject       $repository;
    private RelevemensuelinterRepository&MockObject $releveRepository;
    private EntityManagerInterface&MockObject       $em;
    private ProposerRepository&MockObject           $proposerRepo;
    private FamilleRepository&MockObject            $familleRepo;
    private AppConfigRepository&MockObject          $appConfigRepo;
    private HoraireinterService $service;

    protected function setUp(): void
    {
        $this->repository       = $this->createMock(HoraireinterRepository::class);
        $this->releveRepository = $this->createMock(RelevemensuelinterRepository::class);
        $this->em               = $this->createMock(EntityManagerInterface::class);
        $this->proposerRepo     = $this->createMock(ProposerRepository::class);
        $this->familleRepo      = $this->createMock(FamilleRepository::class);
        $this->appConfigRepo    = $this->createMock(AppConfigRepository::class);

        $defaultConfig = new AppConfig();
        $this->appConfigRepo->method('getConfig')->willReturn($defaultConfig);

        $this->service = new HoraireinterService(
            $this->repository,
            $this->releveRepository,
            $this->em,
            $this->proposerRepo,
            $this->familleRepo,
            $this->appConfigRepo,
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Fonctionnalité 1 — Ajouter des heures travaillées
    // ──────────────────────────────────────────────────────────────────────────

    public function testAjouterPrestation_persistsEntityAndReturnsIt(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->proposerRepo->method('isIntervenantAssignedToFamille')->willReturn(true);
        $this->repository->method('existsDoublon')->willReturn(false);
        $this->repository->method('existsDoublonFamilleDate')->willReturn(false);

        $donnees = [
            'numFam'          => 'FAM001',
            'nomFam'          => 'Famille Test',
            'numInter'        => 42,
            'datePresta'      => '2026-05-07',
            'heureDebutPresta'=> '08:00',
            'heureFinPresta'  => '12:00',
            'typePresta'      => 'ENFA',
            'kmAvecEnfant'    => '10',
        ];

        $result = $this->service->ajouterPrestation($donnees);

        $this->assertInstanceOf(Horaireinter::class, $result);
        $this->assertSame('FAM001', $result->getNumFam());
        $this->assertSame(42, $result->getNumInter());
        $this->assertSame('ENFA', $result->getTypePresta());
    }

    public function testAjouterPrestation_calculatesHeuresTotal(): void
    {
        $this->em->method('persist');
        $this->em->method('flush');

        $donnees = [
            'numFam'          => null,
            'nomFam'          => 'Famille A',
            'numInter'        => 1,
            'datePresta'      => '2026-05-07',
            'heureDebutPresta'=> '09:00',
            'heureFinPresta'  => '12:30',
            'typePresta'      => 'MENA',
            'kmAvecEnfant'    => null,
        ];

        $result = $this->service->ajouterPrestation($donnees);

        // 9h00 → 12h30 = 3.5 heures
        $this->assertEqualsWithDelta(3.5, $result->getHeuresTotal(), 0.01);
    }

    public function testAjouterPrestation_setsDefaultFlags(): void
    {
        $this->em->method('persist');
        $this->em->method('flush');
        $this->proposerRepo->method('isIntervenantAssignedToFamille')->willReturn(true);
        $this->repository->method('existsDoublon')->willReturn(false);
        $this->repository->method('existsDoublonFamilleDate')->willReturn(false);

        $donnees = [
            'numFam'          => 'FAM002',
            'nomFam'          => 'Famille B',
            'numInter'        => 2,
            'datePresta'      => '2026-05-01',
            'heureDebutPresta'=> '10:00',
            'heureFinPresta'  => '14:00',
            'typePresta'      => 'ENFA',
            'kmAvecEnfant'    => null,
        ];

        $result = $this->service->ajouterPrestation($donnees);

        $this->assertFalse($result->isDesactiver());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Fonctionnalité 2 — Limite de jours (nbrJourSaisie)
    // ──────────────────────────────────────────────────────────────────────────

    public function testPeutSaisirHeures_dateAujourdHui_returnsTrue(): void
    {
        $this->assertTrue($this->service->peutSaisirHeures(new \DateTime()));
    }

    public function testPeutSaisirHeures_dateHier_returnsTrue(): void
    {
        $hier = (new \DateTime())->modify('-1 day');
        $this->assertTrue($this->service->peutSaisirHeures($hier));
    }

    public function testPeutSaisirHeures_dateTropAncienne_returnsFalse(): void
    {
        // configuration.json a nbrJourSaisie=7, donc 30 jours dépasse la limite
        $vieille = (new \DateTime())->modify('-30 days');
        $this->assertFalse($this->service->peutSaisirHeures($vieille));
    }

    public function testGetNbrJourSaisie_returnsPositiveInteger(): void
    {
        $nbr = $this->service->getNbrJourSaisie();
        $this->assertIsInt($nbr);
        $this->assertGreaterThan(0, $nbr);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Fonctionnalité 5 — Relevé mensuel : structure JSON correcte
    // ──────────────────────────────────────────────────────────────────────────

    public function testGetReleveData_returnsExpectedTopLevelKeys(): void
    {
        $this->repository->method('findByIntervenantPeriodType')->willReturn([]);
        $this->releveRepository->method('findByMoisAnneeIntervenant')->willReturn(null);

        $data = $this->service->getReleveData(1, 'ENFA', 0);

        foreach (['type', 'periode', 'familles', 'jours', 'totaux', 'signer'] as $key) {
            $this->assertArrayHasKey($key, $data, "Clé '$key' manquante dans getReleveData()");
        }
    }

    public function testGetReleveData_periodeContainsCurrentYear(): void
    {
        $this->repository->method('findByIntervenantPeriodType')->willReturn([]);
        $this->releveRepository->method('findByMoisAnneeIntervenant')->willReturn(null);

        $data = $this->service->getReleveData(1, 'ENFA', 0);

        $this->assertSame(date('Y'), $data['periode']['anner']);
        $this->assertNotEmpty($data['periode']['mois']);
        $this->assertNotEmpty($data['periode']['fin']);
    }

    public function testGetReleveData_joursCouvrentPeriode(): void
    {
        $this->repository->method('findByIntervenantPeriodType')->willReturn([]);
        $this->releveRepository->method('findByMoisAnneeIntervenant')->willReturn(null);

        $data = $this->service->getReleveData(1, 'ENFA', 0);

        // Même calcul que le service : du 25 du mois précédent au 24 du mois courant
        $year  = (int)date('Y');
        $month = (int)date('m');
        $start = new \DateTime(sprintf('%04d-%02d-25', $year, $month));
        $start->modify('-1 month');
        $end   = new \DateTime(sprintf('%04d-%02d-24', $year, $month));
        $expected = (int)$start->diff($end)->days + 1;

        $this->assertCount($expected, $data['jours']);
    }

    public function testGetReleveData_jourContainsRequiredFields(): void
    {
        $this->repository->method('findByIntervenantPeriodType')->willReturn([]);
        $this->releveRepository->method('findByMoisAnneeIntervenant')->willReturn(null);

        $data = $this->service->getReleveData(1, 'ENFA', 0);

        $this->assertNotEmpty($data['jours']);
        $jour = $data['jours'][0];
        foreach (['date', 'jour', 'numeroJour', 'semaine'] as $field) {
            $this->assertArrayHasKey($field, $jour, "Champ '$field' manquant dans jours[]");
        }
        // Le premier jour de la période est le 25 du mois précédent
        $this->assertSame(25, $jour['numeroJour']);
    }

    public function testGetReleveData_groupsPrestationsByFamily(): void
    {
        $p1 = $this->buildPrestation('Famille A', '08:00', '12:00');
        $p2 = $this->buildPrestation('Famille A', '14:00', '17:00');
        $p3 = $this->buildPrestation('Famille B', '09:00', '11:00');

        $this->repository->method('findByIntervenantPeriodType')->willReturn([$p1, $p2, $p3]);
        $this->releveRepository->method('findByMoisAnneeIntervenant')->willReturn(null);

        $data = $this->service->getReleveData(1, 'ENFA', 0);

        $nomsFamilles = array_column($data['familles'], 'nomFam');
        $this->assertContains('Famille A', $nomsFamilles);
        $this->assertContains('Famille B', $nomsFamilles);
        $this->assertCount(2, $data['familles']);
    }

    public function testGetReleveData_calculatesTotalSecondes(): void
    {
        // 08:00 → 12:00 = 4h = 14400 secondes
        $p = $this->buildPrestation('Famille A', '08:00', '12:00');
        $this->repository->method('findByIntervenantPeriodType')->willReturn([$p]);
        $this->releveRepository->method('findByMoisAnneeIntervenant')->willReturn(null);

        $data = $this->service->getReleveData(1, 'ENFA', 0);

        $fam = $data['familles'][0];
        $this->assertSame(14400, $fam['totalSecondes']);
    }

    public function testGetReleveData_signerFalse_whenNoReleve(): void
    {
        $this->repository->method('findByIntervenantPeriodType')->willReturn([]);
        $this->releveRepository->method('findByMoisAnneeIntervenant')->willReturn(null);

        $data = $this->service->getReleveData(1, 'ENFA', 0);

        $this->assertFalse($data['signer']['etat']);
        $this->assertSame('', $data['signer']['date']);
    }

    public function testGetReleveData_signerTrue_whenReleve_isSigned(): void
    {
        $releve = new \App\Entity\Horaire\Relevemensuelinter();
        $releve->setSigner(true);
        $releve->setSignerLe(new \DateTime('2026-05-07'));

        $this->repository->method('findByIntervenantPeriodType')->willReturn([]);
        $this->releveRepository->method('findByMoisAnneeIntervenant')->willReturn($releve);

        $data = $this->service->getReleveData(1, 'ENFA', 0);

        $this->assertTrue($data['signer']['etat']);
        $this->assertSame('07/05/2026', $data['signer']['date']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Fonctionnalité 6 — Heures hors structure
    // ──────────────────────────────────────────────────────────────────────────

    public function testAjouterHeuresHorsStructure_callsRepositoryWithCorrectMoisAnnee(): void
    {
        $this->releveRepository
            ->expects($this->once())
            ->method('ajouterHeureDehors')
            ->with('05/2026', 5, 'MENA', $this->isInstanceOf(\DateTimeInterface::class));

        $result = $this->service->ajouterHeuresHorsStructure(5, 8, 30, '2026-05-31', 'MENA');

        $this->assertTrue($result['success']);
    }

    public function testAjouterHeuresHorsStructure_derivesCorrectMoisAnneeFromPeriodeFin(): void
    {
        $capturedMoisAnnee = null;

        $this->releveRepository
            ->method('ajouterHeureDehors')
            ->willReturnCallback(function (string $moisAnnee) use (&$capturedMoisAnnee) {
                $capturedMoisAnnee = $moisAnnee;
                return new \App\Entity\Horaire\Relevemensuelinter();
            });

        $this->service->ajouterHeuresHorsStructure(1, 2, 0, '2026-03-31', 'ENFA');

        $this->assertSame('03/2026', $capturedMoisAnnee);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Fonctionnalité 7 — Signer le relevé
    // ──────────────────────────────────────────────────────────────────────────

    public function testSignerReleve_moisCourant_returnsSuccess(): void
    {
        $this->releveRepository
            ->expects($this->once())
            ->method('signerReleve')
            ->willReturn(new \App\Entity\Horaire\Relevemensuelinter());

        $periodeFin = (new \DateTime('last day of this month'))->format('Y-m-d');
        $result = $this->service->signerReleve(1, 'ENFA', $periodeFin);

        $this->assertTrue($result['success']);
    }

    public function testSignerReleve_moisPrecedent_returnsSuccess(): void
    {
        $this->releveRepository
            ->expects($this->once())
            ->method('signerReleve')
            ->willReturn(new \App\Entity\Horaire\Relevemensuelinter());

        $periodeFin = (new \DateTime('last day of last month'))->format('Y-m-d');
        $result = $this->service->signerReleve(1, 'ENFA', $periodeFin);

        $this->assertTrue($result['success']);
    }

    public function testSignerReleve_moisTropAncien_blocksSigning(): void
    {
        $this->releveRepository->expects($this->never())->method('signerReleve');

        $periodeFin = (new \DateTime())->modify('-3 months')->format('Y-m-d');
        $result = $this->service->signerReleve(1, 'ENFA', $periodeFin);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testSignerReleve_moisFutur_blocksSigning(): void
    {
        $this->releveRepository->expects($this->never())->method('signerReleve');

        $periodeFin = (new \DateTime())->modify('+1 month')->format('Y-m-d');
        $result = $this->service->signerReleve(1, 'ENFA', $periodeFin);

        $this->assertFalse($result['success']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helper
    // ──────────────────────────────────────────────────────────────────────────

    private function buildPrestation(string $nomFam, string $debut, string $fin): Horaireinter
    {
        $h = new Horaireinter();
        $h->setNomFam($nomFam);
        $h->setDatePresta(new \DateTime('first day of this month'));
        $h->setHeureDebutPresta(new \DateTime($debut));
        $h->setHeureFinPresta(new \DateTime($fin));
        $h->setTypePresta('ENFA');
        $h->setNumInter(1);
        $h->setAjouterLe(new \DateTime());
        $h->setDesactiver(false);
        return $h;
    }
}
