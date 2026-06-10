<?php

namespace App\Tests\Service;

use App\Entity\Horaire\TarifFamille;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour TarifFamille.
 * Couvre la fonctionnalité exonereKm — exemption kilométrique par service.
 */
#[CoversClass(TarifFamille::class)]
class TarifFamilleTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────────
    // Fonctionnalité 9 — Exemption kilométrique par famille / service
    // ──────────────────────────────────────────────────────────────────────────

    public function testIsExonereKm_defaultFalse(): void
    {
        $tarif = new TarifFamille();
        $this->assertFalse($tarif->isExonereKm());
    }

    public function testSetExonereKm_true_returnsTrue(): void
    {
        $tarif = new TarifFamille();
        $tarif->setExonereKm(true);
        $this->assertTrue($tarif->isExonereKm());
    }

    public function testSetExonereKm_false_returnsFalse(): void
    {
        $tarif = new TarifFamille();
        $tarif->setExonereKm(true);
        $tarif->setExonereKm(false);
        $this->assertFalse($tarif->isExonereKm());
    }

    public function testSetExonereKm_returnsFluentInterface(): void
    {
        $tarif = new TarifFamille();
        $result = $tarif->setExonereKm(true);
        $this->assertSame($tarif, $result);
    }

    public function testTarifPlaceholder_tauxHoraireZero_estDetecte(): void
    {
        // Un tarif placeholder (créé pour exonération sans taux réel) a tauxHoraire = 0.00
        $tarif = new TarifFamille();
        $tarif->setTauxHoraire('0.00');
        $tarif->setExonereKm(true);

        $this->assertTrue($tarif->isExonereKm());
        $this->assertEquals(0.0, (float)$tarif->getTauxHoraire());
    }

    public function testTarifAvecTauxReel_peutEtreExonere(): void
    {
        // Une famille avec un taux réel peut aussi être exonérée de km
        $tarif = new TarifFamille();
        $tarif->setTauxHoraire('12.50');
        $tarif->setExonereKm(true);

        $this->assertTrue($tarif->isExonereKm());
        $this->assertGreaterThan(0, (float)$tarif->getTauxHoraire());
    }

    public function testTarifGE_exonereKm_independantDeTarifM(): void
    {
        // L'exemption GE et M sont indépendantes — deux entités séparées
        $tarifGE = new TarifFamille();
        $tarifGE->setTypePresta('GE');
        $tarifGE->setExonereKm(true);

        $tarifM = new TarifFamille();
        $tarifM->setTypePresta('M');
        $tarifM->setExonereKm(false);

        $this->assertTrue($tarifGE->isExonereKm());
        $this->assertFalse($tarifM->isExonereKm());
    }
}
