<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when a value object receives a value that fails its invariants.
 *
 * Value objects validate themselves on construction. Use this exception
 * when the provided raw value does not conform to the expected format or range.
 * Examples: malformed UUID, negative price, invalid email format.
 *
 * Maps to HTTP 422 Unprocessable Entity at the infrastructure boundary.
 */
final class InvalidValueObjectException extends DomainException
{
    /**
     * Creates an exception for an invalid value in a specific value object type.
     *
     * @param class-string $valueObjectClass Fully qualified class name of the value object
     * @param string       $value            The invalid value that was provided
     * @param string       $reason           Why the value is considered invalid
     */
    public static function forValue(string $valueObjectClass, string $value, string $reason): self
    {
        $shortName = substr(strrchr($valueObjectClass, '\\') ?: $valueObjectClass, 1);

        return new self(
            sprintf('Invalid value for %s: "%s". Reason: %s', $shortName, $value, $reason)
        );
    }
}
