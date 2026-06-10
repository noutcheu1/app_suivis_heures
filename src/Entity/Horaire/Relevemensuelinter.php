<?php

namespace App\Entity\Horaire;

use App\Repository\RelevemensuelinterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RelevemensuelinterRepository::class)]
#[ORM\Table(name: 'relevemensuelinter')]
class Relevemensuelinter
{
    #[ORM\Id]
    #[ORM\Column(length: 7)]
    private ?string $moisannee = null;

    #[ORM\Id]
    #[ORM\Column(name: 'numInter')]
    private ?int $numInter = null;

    #[ORM\Id]
    #[ORM\Column(name: 'typePresta', length: 4)]
    private ?string $typePresta = null;

    #[ORM\Column(name: 'heureDehors', type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureDehors = null;

    #[ORM\Column(name: 'heureDehorsAjouterLe', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureDehorsAjouterLe = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $signer = false;

    #[ORM\Column(name: 'signerLe', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $signerLe = null;

    #[ORM\Column(name: 'telechargerLe', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $telechargerLe = null;

    public function getMoisannee(): ?string { return $this->moisannee; }
    public function setMoisannee(string $moisannee): static { $this->moisannee = $moisannee; return $this; }

    public function getNumInter(): ?int { return $this->numInter; }
    public function setNumInter(int $numInter): static { $this->numInter = $numInter; return $this; }

    public function getTypePresta(): ?string { return $this->typePresta; }
    public function setTypePresta(string $typePresta): static { $this->typePresta = $typePresta; return $this; }

    public function getHeureDehors(): ?\DateTimeInterface { return $this->heureDehors; }
    public function setHeureDehors(?\DateTimeInterface $heureDehors): static { $this->heureDehors = $heureDehors; return $this; }

    public function getHeureDehorsAjouterLe(): ?\DateTimeInterface { return $this->heureDehorsAjouterLe; }
    public function setHeureDehorsAjouterLe(?\DateTimeInterface $heureDehorsAjouterLe): static { $this->heureDehorsAjouterLe = $heureDehorsAjouterLe; return $this; }

    public function isSigner(): ?bool { return $this->signer; }
    public function setSigner(bool $signer): static { $this->signer = $signer; return $this; }

    public function getSignerLe(): ?\DateTimeInterface { return $this->signerLe; }
    public function setSignerLe(?\DateTimeInterface $signerLe): static { $this->signerLe = $signerLe; return $this; }

    public function getTelechargerLe(): ?\DateTimeInterface { return $this->telechargerLe; }
    public function setTelechargerLe(?\DateTimeInterface $telechargerLe): static { $this->telechargerLe = $telechargerLe; return $this; }

    public function __toString(): string
    {
        return sprintf('Relevé %s - Intervenant %d (%s)', $this->moisannee, $this->numInter, $this->typePresta);
    }
}
