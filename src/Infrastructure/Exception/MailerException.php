<?php

declare(strict_types=1);

namespace App\Infrastructure\Exception;

use Throwable;

/**
 * Thrown when sending an email through the mailer transport fails.
 *
 * Wraps Symfony Mailer transport exceptions to keep the application layer
 * decoupled from the mailing infrastructure.
 *
 * Maps to HTTP 500 Internal Server Error.
 */
final class MailerException extends InfrastructureException
{
    /**
     * Creates an exception for a failed email send attempt.
     *
     * @param string         $recipient The intended recipient address
     * @param Throwable|null $previous  The original mailer exception
     */
    public static function failedToSend(string $recipient, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to send email to "%s".', $recipient),
            0,
            $previous
        );
    }
}
