<?php

namespace App\Exception;

/**
 * Exception lancée quand on essaie de réutiliser un ancien mot de passe.
 */
final class PasswordReuseException extends \RuntimeException
{
}

