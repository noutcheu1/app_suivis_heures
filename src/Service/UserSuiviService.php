<?php

namespace App\Service;

use App\Entity\Horaire\UserSuivi;
use App\Repository\CandidatRepository;
use App\Repository\UserSuiviRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserSuiviService
{
    public function __construct(
        private UserSuiviRepository $repository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private CandidatRepository $candidatRepository,
    ) {}

    /**
     * Authentifie un utilisateur par identifiant et mot de passe
     */
    public function authentifier(string $identifiant, string $motDePasse): ?UserSuivi
    {
        $user = $this->repository->findByIdentifiant($identifiant);

        if (!$user) {
            return null;
        }

        if (password_verify($motDePasse, $user->getPassword())) {
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
    public function creerUtilisateur(string $identifiant, string $motDePasse, string $role = 'intervenant'): UserSuivi
    {
        $user = new UserSuivi();
        $user->setUsername($identifiant);
        $user->setRole($role);
        // L'email n'est récupéré que pour un intervenant, depuis la table Candidat
        // (le login intervenant correspond au numSS, clé du candidat).
        if ($role === 'intervenant') {
            $user->setEmail($this->candidatRepository->findByNumSs($identifiant)?->getEmail());
        }
        $user->setPassword($this->passwordHasher->hashPassword($user, $motDePasse));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function mettreAJourMotDePasse(string $identifiant, string $nouveauMotDePasse): bool
    {
        $user = $this->repository->findByIdentifiant($identifiant);

        if (!$user) {
            return false;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $nouveauMotDePasse));
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
