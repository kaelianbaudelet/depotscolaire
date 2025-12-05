<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\InvalidCurrentPasswordException;
use App\Exception\PasswordReuseException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordManager
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new InvalidCurrentPasswordException('Invalid current password.');
        }

        $this->assertPasswordIsNew($user, $newPassword);

        $this->updatePassword($user, $newPassword);
    }

    public function resetPassword(User $user, string $newPassword): void
    {
        $this->assertPasswordIsNew($user, $newPassword);

        $this->updatePassword($user, $newPassword);
    }

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

