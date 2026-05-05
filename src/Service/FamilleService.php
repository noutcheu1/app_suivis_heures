<?php

namespace App\Service;

use App\Entity\Famille;
use App\Repository\FamilleRepository;
use Doctrine\ORM\EntityManagerInterface;

class FamilleService
{
    public function __construct(
        private FamilleRepository $repository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Retourne toutes les familles non archivées
     */
    public function getToutesLesFamilles(): array
    {
        return $this->repository->findAllNonArchived();
    }

    /**
     * Retourne une famille par son numéro
     */
    public function getFamilleParNumero(string $numero): ?Famille
    {
        return $this->repository->findByNumero($numero);
    }

    /**
     * Recherche des familles par nom ou ville
     */
    public function rechercherFamilles(string $terme): array
    {
        return $this->repository->findByNomOrVille($terme);
    }

    /**
     * Retourne les familles par ville
     */
    public function getFamillesParVille(string $ville): array
    {
        return $this->repository->findByVille($ville);
    }

    /**
     * Compte le nombre de familles actives
     */
    public function compterActives(): int
    {
        return $this->repository->countActives();
    }

    /**
     * Archive une famille
     */
    public function archiverFamille(int $id): bool
    {
        $famille = $this->repository->find($id);
        if (!$famille) {
            return false;
        }

        $famille->setArchive(true);
        $famille->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();
        return true;
    }

    /**
     * Met à jour les informations d'une famille
     */
    public function mettreAJourFamille(Famille $famille): void
    {
        $famille->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * Crée une nouvelle famille
     */
    public function creerFamille(array $donnees): Famille
    {
        $famille = new Famille();
        
        // Mapping des données vers l'entité
        $famille->setNumeroFamille($donnees['numeroFamille'] ?? '');
        $famille->setNomFamille($donnees['nomFamille'] ?? null);
        $famille->setAdresse($donnees['adresse'] ?? null);
        $famille->setCodePostal($donnees['codePostal'] ?? null);
        $famille->setVille($donnees['ville'] ?? null);
        $famille->setTelDom($donnees['telDom'] ?? null);
        $famille->setEmail($donnees['email'] ?? null);
        
        // Champs par défaut
        $famille->setArchive(false);
        $famille->setCreatedAt(new \DateTimeImmutable());
        $famille->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($famille);
        $this->entityManager->flush();
        
        return $famille;
    }
}
