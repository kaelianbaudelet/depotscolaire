<?php

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Exception\InvalidPasswordResetTokenException;
use App\Repository\PasswordResetTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gère le cycle de vie de la réinitialisation de mot de passe.
 * Création de tokens, validation, expiration... tout est là.
 */
class PasswordResetManager
{
    private const TOKEN_TTL = '+1 hour';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PasswordResetTokenRepository $tokenRepository,
    ) {
    }

    /**
     * Crée un token sécurisé pour un utilisateur.
     * Invalide les anciens tokens pour faire le ménage.
     *
     * @return array{0: PasswordResetToken, 1: string}
     */
    public function createToken(User $user): array
    {
        $this->invalidateExistingTokens($user);

        $selector = bin2hex(random_bytes(16));
        $plainToken = bin2hex(random_bytes(32));

        $resetToken = (new PasswordResetToken())
            ->setUser($user)
            ->setSelector($selector)
            ->setHashedToken($this->hashToken($plainToken))
            ->setExpiresAt(new DateTimeImmutable(self::TOKEN_TTL));

        $this->entityManager->persist($resetToken);
        $this->entityManager->flush();

        return [$resetToken, $plainToken];
    }

    /**
     * Vérifie si un token reçu est valide (existant, pas expiré, pas déjà utilisé).
     */
    public function validateToken(string $selector, string $plainToken): PasswordResetToken
    {
        $token = $this->tokenRepository->findOneBy(['selector' => $selector]);

        if (!$token instanceof PasswordResetToken) {
            throw new InvalidPasswordResetTokenException('Token not found.');
        }

        if ($token->isUsed()) {
            throw new InvalidPasswordResetTokenException('Token already used.');
        }

        if ($token->isExpired()) {
            throw new InvalidPasswordResetTokenException('Token expired.');
        }

        if (!hash_equals($token->getHashedToken(), $this->hashToken($plainToken))) {
            throw new InvalidPasswordResetTokenException('Token mismatch.');
        }

        return $token;
    }

    public function markTokenUsed(PasswordResetToken $token): void
    {
        $token->markUsed();
        $this->entityManager->flush();
    }

    private function invalidateExistingTokens(User $user): void
    {
        $existingTokens = $this->tokenRepository->findBy(['user' => $user]);

        foreach ($existingTokens as $token) {
            $this->entityManager->remove($token);
        }

        if ($existingTokens !== []) {
            $this->entityManager->flush();
        }
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
