<?php

namespace App\Entity\Horaire;

use App\Repository\HoraireinterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HoraireinterRepository::class)]
#[ORM\Table(name: 'horaireinter', indexes: [
    new ORM\Index(name: 'idx_numFam',     columns: ['numFam']),
    new ORM\Index(name: 'idx_numInter',   columns: ['numInter']),
    new ORM\Index(name: 'idx_datePresta', columns: ['datePresta']),
])]

class Horaireinter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'numFam', type: 'string', nullable: true)]
    private ?string $numFam = null;

    #[ORM\Column(name: 'nomFam', type: 'string')]
    private ?string $nomFam = null;

    #[ORM\Column(name: 'numInter', type: 'integer')]
    private int $numInter = 0;

    #[ORM\Column(name: 'datePresta', type: 'date')]
    private \DateTimeInterface $datePresta;

    #[ORM\Column(name: 'heureDebutPresta', type: 'time', nullable: true)]
    private ?\DateTimeInterface $heureDebutPresta = null;

    #[ORM\Column(name: 'heureFinPresta', type: 'time', nullable: true)]
    private ?\DateTimeInterface $heureFinPresta = null;

    #[ORM\Column(name: 'typePresta', length: 4)]
    private ?string $typePresta = null;

    #[ORM\Column(name: 'kmAvecEnfant', type: Types::DECIMAL, precision: 5, scale: 1, nullable: true)]
    private ?string $kmAvecEnfant = null;

    #[ORM\Column(name: 'ajouterLe', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $ajouterLe = null;

    #[ORM\Column(name: 'modifierLe', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $modifierLe = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?bool $desactiver = false;

    #[ORM\Column(name: 'heureDebutFam', type: 'time', nullable: true)]
    private ?\DateTimeInterface $heureDebutFam = null;

    #[ORM\Column(name: 'heureFinFam', type: 'time', nullable: true)]
    private ?\DateTimeInterface $heureFinFam = null;

    #[ORM\Column(name: 'declarerLeFam', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $declarerLeFam = null;

    /** 'inter' | 'fam' | null — choix admin pour la facturation */
    #[ORM\Column(name: 'sourceFacturation', length: 5, nullable: true)]
    private ?string $sourceFacturation = null;

    /** Computed field — not stored in DB */
    private float $heuresTotal = 0.0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumFam(): ?string
    {
        return $this->numFam;
    }

    public function setNumFam(?string $numFam): static
    {
        $this->numFam = $numFam;
        return $this;
    }

    public function getNomFam(): ?string
    {
        return $this->nomFam;
    }

    public function setNomFam(?string $nomFam): static
    {
        $this->nomFam = $nomFam;
        return $this;
    }

    public function getNumInter(): ?int
    {
        return $this->numInter;
    }

    public function setNumInter(int $numInter): static
    {
        $this->numInter = $numInter;
        return $this;
    }

    public function getDatePresta(): ?\DateTimeInterface
    {
        return $this->datePresta;
    }

    public function setDatePresta(\DateTimeInterface $datePresta): static
    {
        $this->datePresta = $datePresta;
        return $this;
    }

    public function getHeureDebutPresta(): ?\DateTimeInterface
    {
        return $this->heureDebutPresta;
    }

    public function setHeureDebutPresta(?\DateTimeInterface $heureDebutPresta): static
    {
        $this->heureDebutPresta = $heureDebutPresta;
        return $this;
    }

    public function getHeureFinPresta(): ?\DateTimeInterface
    {
        return $this->heureFinPresta;
    }

    public function setHeureFinPresta(?\DateTimeInterface $heureFinPresta): static
    {
        $this->heureFinPresta = $heureFinPresta;
        return $this;
    }

    public function getTypePresta(): ?string
    {
        return $this->typePresta;
    }

    public function setTypePresta(string $typePresta): static
    {
        $this->typePresta = $typePresta;
        return $this;
    }

    public function getKmAvecEnfant(): ?string
    {
        return $this->kmAvecEnfant;
    }

    public function setKmAvecEnfant(?string $kmAvecEnfant): static
    {
        $this->kmAvecEnfant = $kmAvecEnfant;
        return $this;
    }

    public function getAjouterLe(): ?\DateTimeInterface
    {
        return $this->ajouterLe;
    }

    public function setAjouterLe(\DateTimeInterface $ajouterLe): static
    {
        $this->ajouterLe = $ajouterLe;
        return $this;
    }

    public function getModifierLe(): ?\DateTimeInterface
    {
        return $this->modifierLe;
    }

    public function setModifierLe(?\DateTimeInterface $modifierLe): static
    {
        $this->modifierLe = $modifierLe;
        return $this;
    }

    public function isDesactiver(): ?bool
    {
        return $this->desactiver;
    }

    public function setDesactiver(bool $desactiver): static
    {
        $this->desactiver = $desactiver;
        return $this;
    }

    public function getHeureDebutFam(): ?\DateTimeInterface
    {
        return $this->heureDebutFam;
    }

    public function setHeureDebutFam(?\DateTimeInterface $heureDebutFam): static
    {
        $this->heureDebutFam = $heureDebutFam;
        return $this;
    }

    public function getHeureFinFam(): ?\DateTimeInterface
    {
        return $this->heureFinFam;
    }

    public function setHeureFinFam(?\DateTimeInterface $heureFinFam): static
    {
        $this->heureFinFam = $heureFinFam;
        return $this;
    }

    public function getDeclarerLeFam(): ?\DateTimeInterface
    {
        return $this->declarerLeFam;
    }

    public function setDeclarerLeFam(?\DateTimeInterface $declarerLeFam): static
    {
        $this->declarerLeFam = $declarerLeFam;
        return $this;
    }

    public function getSourceFacturation(): ?string
    {
        return $this->sourceFacturation;
    }

    public function setSourceFacturation(?string $sourceFacturation): static
    {
        $this->sourceFacturation = $sourceFacturation;
        return $this;
    }

    public function getHeuresTotal(): float
    {
        return $this->heuresTotal;
    }

    public function setHeuresTotal(float $heuresTotal): static
    {
        $this->heuresTotal = $heuresTotal;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s (%s)',
            $this->nomFam,
            $this->datePresta?->format('d/m/Y'),
            $this->typePresta
        );
    }
}
