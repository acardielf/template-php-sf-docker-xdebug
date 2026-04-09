<?php

declare(strict_types=1);

namespace App\Application\Exception;

/**
 * Thrown when the current actor lacks permission to execute an operation.
 *
 * Raised in command/query handlers when the authenticated user does not
 * hold the required role or ownership over the requested resource.
 *
 * Maps to HTTP 403 Forbidden at the infrastructure boundary.
 */
final class UnauthorizedException extends ApplicationException
{
    /**
     * Creates an exception for a specific denied operation.
     *
     * @param string $operation  The operation that was denied (e.g. "delete:order")
     * @param string $actorId    Identifier of the actor who attempted it
     */
    public static function forOperation(string $operation, string $actorId): self
    {
        return new self(
            sprintf('Actor "%s" is not authorized to perform "%s".', $actorId, $operation)
        );
    }
}
