<?php

namespace App\Service;

use App\Entity\User2;
use App\Repository\User2Repository;
use Doctrine\ORM\EntityManagerInterface;

class User2Service
{
    public function __construct(
        private User2Repository $repository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Authentifie un utilisateur par identifiant et mot de passe
     */
    public function authentifier(string $identifiant, string $motDePasse): ?User2
    {
        $user = $this->repository->findByIdentifiant($identifiant);
        
        if (!$user) {
            return null;
        }

        // Vérification du mot de passe (à adapter selon le hash utilisé)
        if (password_verify($motDePasse, $user->getMdp())) {
            return $user;
        }

        // Pour la compatibilité avec l'ancien système (mot de passe en clair)
        if ($user->getMdp() === $motDePasse) {
            // Mettre à jour avec un hash sécurisé
            $user->setMdp(password_hash($motDePasse, PASSWORD_DEFAULT));
            $this->entityManager->flush();
            return $user;
        }

        return null;
    }

    /**
     * Vérifie si un identifiant existe
     */
    public function identifiantExiste(string $identifiant): bool
    {
        return $this->repository->identifiantExists($identifiant);
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function creerUtilisateur(string $identifiant, string $motDePasse): User2
    {
        $user = new User2();
        $user->setIdentifiant($identifiant);
        $user->setMdp(password_hash($motDePasse, PASSWORD_DEFAULT));
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    /**
     * Met à jour le mot de passe d'un utilisateur
     */
    public function mettreAJourMotDePasse(string $identifiant, string $nouveauMotDePasse): bool
    {
        $user = $this->repository->findByIdentifiant($identifiant);
        
        if (!$user) {
            return false;
        }

        $user->setMdp(password_hash($nouveauMotDePasse, PASSWORD_DEFAULT));
        $this->entityManager->flush();
        
        return true;
    }

    /**
     * Compte le nombre d'utilisateurs
     */
    public function compterUtilisateurs(): int
    {
        return $this->repository->countUsers();
    }
}
