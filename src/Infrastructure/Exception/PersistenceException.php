<?php

declare(strict_types=1);

namespace App\Infrastructure\Exception;

use Throwable;

/**
 * Thrown when a database or ORM operation fails.
 *
 * Wraps Doctrine/PDO exceptions so that the application and domain layers
 * remain decoupled from the persistence technology.
 *
 * Maps to HTTP 500 Internal Server Error. Do NOT expose the wrapped
 * exception message to the client — it may contain sensitive query details.
 */
final class PersistenceException extends InfrastructureException
{
    /**
     * Creates a persistence exception wrapping a lower-level database error.
     *
     * @param string         $operation The persistence operation that failed (e.g. "save", "find", "delete")
     * @param class-string   $entity    The entity class involved in the operation
     * @param Throwable|null $throwable The original database exception
     */
    public static function forOperation(string $operation, string $entity, ?Throwable $throwable = null): self
    {
        $shortName = substr(strrchr($entity, '\\') ?: $entity, 1);

        return new self(
            sprintf('Persistence error during "%s" on entity "%s".', $operation, $shortName),
            0,
            $throwable
        );
    }
}
