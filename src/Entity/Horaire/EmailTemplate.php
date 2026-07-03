<?php

namespace App\Entity\Horaire;

use App\Repository\EmailTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Modèle d'email personnalisable par l'admin (sujet + corps avec variables).
 * Identifié par une clé stable (ex. 'mdp_oublie', 'releve_signe').
 */
#[ORM\Entity(repositoryClass: EmailTemplateRepository::class)]
#[ORM\Table(name: 'email_templates')]
class EmailTemplate
{
    #[ORM\Id]
    #[ORM\Column(name: 'cle', type: 'string', length: 50)]
    private string $cle;

    #[ORM\Column(name: 'sujet', type: 'string', length: 255)]
    private string $sujet = '';

    #[ORM\Column(name: 'corps', type: Types::TEXT)]
    private string $corps = '';

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    public function __construct(string $cle = '')
    {
        $this->cle = $cle;
        $this->updatedAt = new \DateTime();
    }

    public function getCle(): string            { return $this->cle; }
    public function setCle(string $c): static   { $this->cle = $c; return $this; }

    public function getSujet(): string          { return $this->sujet; }
    public function setSujet(string $s): static  { $this->sujet = $s; return $this; }

    public function getCorps(): string          { return $this->corps; }
    public function setCorps(string $c): static  { $this->corps = $c; return $this; }

    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function touch(): static { $this->updatedAt = new \DateTime(); return $this; }
}
