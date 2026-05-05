<?php

namespace App\Service;

use App\Entity\Intervenant;
use App\Repository\IntervenantRepository;
use Doctrine\ORM\EntityManagerInterface;

class IntervenantService
{
    public function __construct(
        private IntervenantRepository $repository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Retourne tous les intervenants non archivés
     */
    public function getTousLesIntervenants(): array
    {
        return $this->repository->findAllNonArchived();
    }

    /**
     * Retourne les informations d'un intervenant
     */
    public function getInfosIntervenant(int $id): ?Intervenant
    {
        return $this->repository->findInfosIntervenant($id);
    }

    /**
     * Retourne un intervenant par son numéro de salarié
     */
    public function getIntervenantParNumSalarie(string $numSalarie): ?Intervenant
    {
        return $this->repository->findByNumSalarie($numSalarie);
    }

    /**
     * Recherche des intervenants par nom ou prénom
     */
    public function rechercherIntervenants(string $terme): array
    {
        return $this->repository->findByNomOrPrenom($terme);
    }

    /**
     * Compte le nombre d'intervenants actifs
     */
    public function compterActifs(): int
    {
        return $this->repository->countActifs();
    }

    /**
     * Archive un intervenant
     */
    public function archiverIntervenant(int $id): bool
    {
        $intervenant = $this->repository->find($id);
        if (!$intervenant) {
            return false;
        }

        $intervenant->setArchive(true);
        $intervenant->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();
        return true;
    }

    /**
     * Désarchive un intervenant
     */
    public function desarchiverIntervenant(int $id): bool
    {
        $intervenant = $this->repository->find($id);
        if (!$intervenant) {
            return false;
        }

        $intervenant->setArchive(false);
        $intervenant->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();
        return true;
    }

    /**
     * Met à jour les informations d'un intervenant
     */
    public function mettreAJourIntervenant(Intervenant $intervenant): void
    {
        $intervenant->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * Crée un nouvel intervenant
     */
    public function creerIntervenant(array $donnees): Intervenant
    {
        $intervenant = new Intervenant();
        
        // Mapping des données vers l'entité
        $intervenant->setNumSalarie($donnees['numSalarie'] ?? null);
        $intervenant->setNom($donnees['nom'] ?? '');
        $intervenant->setPrenom($donnees['prenom'] ?? '');
        $intervenant->setEmail($donnees['email'] ?? null);
        $intervenant->setTelPortable($donnees['telPortable'] ?? null);
        $intervenant->setAdresse($donnees['adresse'] ?? null);
        $intervenant->setCodePostal($donnees['codePostal'] ?? null);
        $intervenant->setVille($donnees['ville'] ?? null);
        $intervenant->setTauxHoraire($donnees['tauxHoraire'] ?? null);
        
        // Champs par défaut
        $intervenant->setArchive(false);
        $intervenant->setCreatedAt(new \DateTimeImmutable());
        $intervenant->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($intervenant);
        $this->entityManager->flush();
        
        return $intervenant;
    }
}
