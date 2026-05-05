<?php

namespace App\Entity;

use App\Repository\TarifRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TarifRepository::class)]
#[ORM\Table(name: 'tarifs_unifiee')]
class Tarif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 4)]
    private ?string $typePrestation = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $libellePrestation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $tarifHoraireBase = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $tarifHoraireMajoration = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $fraisGestion = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $kmEnfants = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $abonnement = null;

    #[ORM\Column(length: 7)]
    private ?string $dateDebutValidite = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $dateFinValidite = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypePrestation(): ?string
    {
        return $this->typePrestation;
    }

    public function setTypePrestation(string $typePrestation): static
    {
        $this->typePrestation = $typePrestation;

        return $this;
    }

    public function getLibellePrestation(): ?string
    {
        return $this->libellePrestation;
    }

    public function setLibellePrestation(?string $libellePrestation): static
    {
        $this->libellePrestation = $libellePrestation;

        return $this;
    }

    public function getTarifHoraireBase(): ?string
    {
        return $this->tarifHoraireBase;
    }

    public function setTarifHoraireBase(string $tarifHoraireBase): static
    {
        $this->tarifHoraireBase = $tarifHoraireBase;

        return $this;
    }

    public function getFraisGestion(): ?string
    {
        return $this->fraisGestion;
    }

    public function setFraisGestion(string $fraisGestion): static
    {
        $this->fraisGestion = $fraisGestion;

        return $this;
    }

    public function getKmEnfants(): ?string
    {
        return $this->kmEnfants;
    }

    public function setKmEnfants(string $kmEnfants): static
    {
        $this->kmEnfants = $kmEnfants;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->libellePrestation ?? $this->typePrestation;
    }
}
