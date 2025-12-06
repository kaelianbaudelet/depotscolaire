<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\InvalidCurrentPasswordException;
use App\Exception\PasswordReuseException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Service de gestion des mots de passe utilisateurs.
 * S'occupe de vérifier l'ancien mot de passe, de hasher le nouveau, et de vérifier l'historique.
 */
class UserPasswordManager
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Change le mot de passe d'un utilisateur connecté (avec vérification de l'actuel).
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new InvalidCurrentPasswordException('Invalid current password.');
        }

        $this->assertPasswordIsNew($user, $newPassword);

        $this->updatePassword($user, $newPassword);
    }

    /**
     * Réinitialise le mot de passe (sans vérifier l'ancien, car il est oublié ou c'est un reset admin).
     */
    public function resetPassword(User $user, string $newPassword): void
    {
        $this->assertPasswordIsNew($user, $newPassword);

        $this->updatePassword($user, $newPassword);
    }

    /**
     * Vérifie que le mot de passe n'a pas déjà été utilisé dans le passé.
     */
    private function assertPasswordIsNew(User $user, string $plainPassword): void
    {
        $hasher = $this->getHasherForUser($user);

        foreach ($user->getPasswordHistory() as $password) {
            if ($hasher->verify($password->getHash(), $plainPassword)) {
                throw new PasswordReuseException('Password has been used before.');
            }
        }
    }

    private function updatePassword(User $user, string $plainPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);

        $user->setPassword($hashedPassword);
        $this->entityManager->flush();
    }

    private function getHasherForUser(User $user): PasswordHasherInterface
    {
        return $this->passwordHasherFactory->getPasswordHasher($user);
    }
}

