<?php

namespace App\Entity\Horaire;

use App\Repository\IntervenantDispoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Disponibilité GÉNÉRALE d'un intervenant pour effectuer des remplacements.
 *
 * Information au niveau de l'intervenant (pas d'un congé) : « en dehors de mes
 * congés, suis-je prêt à faire des remplacements ? ». Une ligne par intervenant.
 */
#[ORM\Entity(repositoryClass: IntervenantDispoRepository::class)]
#[ORM\Table(name: 'intervenant_dispo')]
class IntervenantDispo
{
    #[ORM\Id]
    #[ORM\Column]
    private int $numInter;

    #[ORM\Column]
    private bool $disponible = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(int $numInter)
    {
        $this->numInter = $numInter;
    }

    public function getNumInter(): int { return $this->numInter; }

    public function isDisponible(): bool { return $this->disponible; }
    public function setDisponible(bool $v): static
    {
        $this->disponible = $v;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
}
