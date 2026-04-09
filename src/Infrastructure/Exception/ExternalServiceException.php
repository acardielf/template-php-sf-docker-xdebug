<?php

declare(strict_types=1);

namespace App\Infrastructure\Exception;

use Throwable;

/**
 * Thrown when a call to an external third-party service fails.
 *
 * Use this for HTTP client failures, API timeouts, unexpected response codes,
 * or deserialization errors from external APIs.
 *
 * Maps to HTTP 502 Bad Gateway or HTTP 500 Internal Server Error
 * depending on whether the failure is on the upstream's side.
 */
final class ExternalServiceException extends InfrastructureException
{
    /**
     * Creates an exception for a failed external service call.
     *
     * @param string         $service  Name of the external service (e.g. "Stripe", "Twilio")
     * @param string         $reason   Short description of what went wrong
     * @param Throwable|null $previous The original underlying exception
     */
    public static function forService(string $service, string $reason, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('External service "%s" failed: %s', $service, $reason),
            0,
            $previous
        );
    }
}
