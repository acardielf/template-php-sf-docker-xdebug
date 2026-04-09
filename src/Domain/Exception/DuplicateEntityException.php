<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when attempting to create an entity that already exists.
 *
 * Use this when a uniqueness invariant of the domain is violated.
 * Examples: registering with an email already in use, creating two
 * orders with the same reference number.
 *
 * Maps to HTTP 409 Conflict at the infrastructure boundary.
 */
final class DuplicateEntityException extends DomainException
{
    /**
     * Creates an exception for a duplicate entity identified by a field value.
     *
     * @param class-string $entityClass Fully qualified class name of the entity
     * @param string       $field       The field that must be unique
     * @param string       $value       The duplicate value
     */
    public static function forField(string $entityClass, string $field, string $value): self
    {
        $shortName = substr(strrchr($entityClass, '\\') ?: $entityClass, 1);

        return new self(
            sprintf('%s with %s "%s" already exists.', $shortName, $field, $value)
        );
    }
}
