<?php

namespace App\Entity;

use App\Repository\Tarifs2Repository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: Tarifs2Repository::class)]
#[ORM\Table(name: 'tarifs2')]
class Tarifs2
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $alheureGE = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $alheureM = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $fraisGestion = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $parIntervention = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $maxParIntervention = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $KMenfants = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $abonnement = null;

    #[ORM\Column(length: 7)]
    private ?string $dateDebut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlheureGE(): ?string
    {
        return $this->alheureGE;
    }

    public function setAlheureGE(string $alheureGE): static
    {
        $this->alheureGE = $alheureGE;

        return $this;
    }

    public function getAlheureM(): ?string
    {
        return $this->alheureM;
    }

    public function setAlheureM(?string $alheureM): static
    {
        $this->alheureM = $alheureM;

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

    public function getParIntervention(): ?string
    {
        return $this->parIntervention;
    }

    public function setParIntervention(string $parIntervention): static
    {
        $this->parIntervention = $parIntervention;

        return $this;
    }

    public function getMaxParIntervention(): ?string
    {
        return $this->maxParIntervention;
    }

    public function setMaxParIntervention(string $maxParIntervention): static
    {
        $this->maxParIntervention = $maxParIntervention;

        return $this;
    }

    public function getKMenfants(): ?string
    {
        return $this->KMenfants;
    }

    public function setKMenfants(string $KMenfants): static
    {
        $this->KMenfants = $KMenfants;

        return $this;
    }

    public function getAbonnement(): ?string
    {
        return $this->abonnement;
    }

    public function setAbonnement(string $abonnement): static
    {
        $this->abonnement = $abonnement;

        return $this;
    }

    public function getDateDebut(): ?string
    {
        return $this->dateDebut;
    }

    public function setDateDebut(string $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            'Tarif %s (depuis %s)',
            $this->id,
            $this->dateDebut
        );
    }
}
