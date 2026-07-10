<?php

namespace App\Repository;

use App\Entity\Horaire\FamilleToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FamilleToken>
 */
class FamilleTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FamilleToken::class);
    }

    /** Jeton d'une famille, créé à la volée s'il n'existe pas encore. */
    public function tokenPour(string $numFam): string
    {
        $existant = $this->find($numFam);
        if ($existant) {
            return $existant->getToken();
        }

        $ft = new FamilleToken($numFam, $this->genererToken());
        $em = $this->getEntityManager();
        $em->persist($ft);
        $em->flush();

        return $ft->getToken();
    }

    /** Résout un jeton en numéro de famille, ou null si inconnu. */
    public function numFamPour(string $token): ?string
    {
        return $this->findOneBy(['token' => $token])?->getNumFam();
    }

    /**
     * Numéros de famille dont le QR a déjà été imprimé au moins une fois.
     *
     * @return string[]
     */
    public function numFamsImprimes(): array
    {
        return array_map(
            static fn ($r) => (string) $r['numFam'],
            $this->createQueryBuilder('t')
                ->select('t.numFam')
                ->where('t.imprimeLe IS NOT NULL')
                ->getQuery()
                ->getScalarResult()
        );
    }

    /** Marque une liste de familles comme « QR imprimé » (crée le token au besoin). */
    public function marquerImprimes(array $numFams): void
    {
        $em = $this->getEntityManager();
        foreach ($numFams as $numFam) {
            $numFam = (string) $numFam;
            if ($numFam === '') {
                continue;
            }
            $ft = $this->find($numFam) ?? new \App\Entity\Horaire\FamilleToken($numFam, $this->genererToken());
            $ft->marquerImprime();
            $em->persist($ft);
        }
        $em->flush();
    }

    /** Jeton unique, haute entropie, URL-safe. */
    private function genererToken(): string
    {
        do {
            $token = bin2hex(random_bytes(12)); // 24 caractères hex
        } while ($this->findOneBy(['token' => $token]) !== null);

        return $token;
    }
}
