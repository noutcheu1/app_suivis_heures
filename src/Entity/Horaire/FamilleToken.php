<?php

namespace App\Entity\Horaire;

use App\Repository\FamilleTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Jeton public opaque d'une famille, utilisé dans les URLs de pointage et le QR
 * code À LA PLACE du numéro de famille — qui est énumérable (M0001, M0002…) et
 * donc exposé au brute-force. Le jeton (haute entropie) n'est pas devinable.
 */
#[ORM\Entity(repositoryClass: FamilleTokenRepository::class)]
#[ORM\Table(name: 'famille_token')]
class FamilleToken
{
    #[ORM\Id]
    #[ORM\Column(length: 20)]
    private string $numFam;

    #[ORM\Column(length: 32, unique: true)]
    private string $token;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** Dernière impression du QR (null = jamais imprimé). */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $imprimeLe = null;

    public function __construct(string $numFam, string $token)
    {
        $this->numFam    = $numFam;
        $this->token     = $token;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getNumFam(): string { return $this->numFam; }
    public function getToken(): string { return $this->token; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getImprimeLe(): ?\DateTimeImmutable { return $this->imprimeLe; }
    public function marquerImprime(): static { $this->imprimeLe = new \DateTimeImmutable(); return $this; }
    public function estImprime(): bool { return $this->imprimeLe !== null; }
}
