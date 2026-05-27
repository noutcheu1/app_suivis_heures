<?php

namespace App\Entity\Horaire;

use App\Repository\AppConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppConfigRepository::class)]
#[ORM\Table(name: 'app_config')]
class AppConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Nombre de jours max en arrière pour saisir des heures */
    #[ORM\Column]
    private int $nbrJourSaisie = 10;

    /** Nombre de paliers de tarif Garde Enfants */
    #[ORM\Column]
    private int $nbrPalierTarifGE = 4;

    /** Nombre de paliers de tarif Ménage */
    #[ORM\Column]
    private int $nbrPalierTarifM = 0;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getNbrJourSaisie(): int { return $this->nbrJourSaisie; }
    public function setNbrJourSaisie(int $v): static { $this->nbrJourSaisie = $v; return $this; }

    public function getNbrPalierTarifGE(): int { return $this->nbrPalierTarifGE; }
    public function setNbrPalierTarifGE(int $v): static { $this->nbrPalierTarifGE = $v; return $this; }

    public function getNbrPalierTarifM(): int { return $this->nbrPalierTarifM; }
    public function setNbrPalierTarifM(int $v): static { $this->nbrPalierTarifM = $v; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touch(): static { $this->updatedAt = new \DateTimeImmutable(); return $this; }
}
