<?php

namespace App\Entity;

use App\Repository\User2Repository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: User2Repository::class)]
#[ORM\Table(name: 'users2')]
class User2
{
    #[ORM\Id]
    #[ORM\Column(length: 21)]
    private ?string $identifiant = null;

    #[ORM\Column(length: 255)]
    private ?string $mdp = null;

    public function getIdentifiant(): ?string
    {
        return $this->identifiant;
    }

    public function setIdentifiant(string $identifiant): static
    {
        $this->identifiant = $identifiant;

        return $this;
    }

    public function getMdp(): ?string
    {
        return $this->mdp;
    }

    public function setMdp(string $mdp): static
    {
        $this->mdp = $mdp;

        return $this;
    }

    public function __toString(): string
    {
        return $this->identifiant;
    }
}
