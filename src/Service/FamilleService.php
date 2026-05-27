<?php

namespace App\Service;

use App\Entity\Principal\Enfant;
use App\Entity\Principal\Famille;
use App\Repository\EnfantRepository;
use App\Repository\FamilleRepository;
use Doctrine\ORM\EntityManagerInterface;

class FamilleService
{
    public function __construct(
        private FamilleRepository      $repository,
        private EnfantRepository       $enfantRepository,
        private EntityManagerInterface $entityManager,
        private FactureService         $factureService,
    ) {}

    /**
     * Retourne toutes les familles non archivées (pour sélection famille occasionnelle).
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
     * Compte les familles ayant un planning actif dans proposer.
     */
    public function countFamilles(): int
    {
        return $this->repository->countAvecPlanningActif();
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
        $famille->setUpdatedAt(new \DateTime());
        
        $this->entityManager->flush();
        return true;
    }

    /**
     * Met à jour les informations d'une famille
     */
    public function mettreAJourFamille(Famille $famille): void
    {
        $famille->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();
    }

    /**
     * @param string $mois Format YYYY-MM
     */
    public function calculerMontantDu(string $familleId, string $mois): float
    {
        return $this->factureService->calculerFacture($familleId, $mois)['montantTotal'];
    }

    /**
     * @deprecated Utiliser FamilleIntervenantService::getFamillesForIntervenant()
     *             qui filtre sur proposer (planning actif).
     */
    public function getFamillesDeIntervenant(int $intervenantId): array
    {
        return $this->repository->findByIntervenantActif($intervenantId);
    }

    /**
     * Crée une nouvelle famille
     */
    /**
     * @return Enfant[]
     */
    public function getEnfantsByFamille(string $numFam, bool $gardeUniquement = false): array
    {
        return $this->enfantRepository->findByFamille($numFam, $gardeUniquement);
    }

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
        $famille->setCreatedAt(new \DateTime());
        $famille->setUpdatedAt(new \DateTime());
        
        $this->entityManager->persist($famille);
        $this->entityManager->flush();
        
        return $famille;
    }
}
