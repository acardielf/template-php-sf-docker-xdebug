<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when a requested domain entity cannot be found.
 *
 * Infrastructure adapters should catch this exception and translate it
 * to the appropriate HTTP 404 response.
 */
final class EntityNotFoundException extends DomainException
{
    /**
     * Creates a new exception for the given entity type and identifier.
     *
     * @param class-string $entityClass Fully qualified class name of the entity
     * @param string       $identifier  The identifier used for the lookup
     */
    public static function forEntity(string $entityClass, string $identifier): self
    {
        $shortName = substr(strrchr($entityClass, '\\') ?: $entityClass, 1);

        return new self(
            sprintf('%s with identifier "%s" was not found.', $shortName, $identifier)
        );
    }
}
