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
    public function creerUtilisateur(string $identifiant, string $motDePasse, string $role = 'intervenant', ?string $email = null): UserSuivi
    {
        $user = new UserSuivi();
        $user->setUsername($identifiant);
        $user->setRole($role);
        // Intervenant : l'identifiant stocké est le numSalarie (RGPD : ni numSS ni téléphone
        // dans la bd horaire). L'email est résolu par l'appelant depuis le dossier chaudoudou.
        if ($role === 'intervenant') {
            $user->setEmail($email);
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
