<?php

namespace App\Exception;

/**
 * Exception lancée quand le token de reset est invalide (expiré, introuvable...).
 */
final class InvalidPasswordResetTokenException extends \RuntimeException
{
}

