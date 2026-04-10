<?php

declare(strict_types=1);

namespace App\Infrastructure\Exception;

use Throwable;

/**
 * Thrown when dispatching a message to the broker fails.
 *
 * Wraps Symfony Messenger transport exceptions so callers do not need
 * to depend on framework-specific exception types.
 *
 * Maps to HTTP 500 Internal Server Error.
 */
final class MessagingException extends InfrastructureException
{
    /**
     * Creates an exception for a failed message dispatch.
     *
     * @param class-string   $messageClass The message class that could not be dispatched
     * @param Throwable|null $throwable    The original transport exception
     */
    public static function failedToDispatch(string $messageClass, ?Throwable $throwable = null): self
    {
        $shortName = substr(strrchr($messageClass, '\\') ?: $messageClass, 1);

        return new self(
            sprintf('Failed to dispatch message "%s" to the broker.', $shortName),
            0,
            $throwable
        );
    }
}
