<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when a domain business rule is violated.
 *
 * Use this when an operation is structurally valid but breaks an invariant
 * of the domain model. Examples: insufficient funds, attempting to cancel
 * an already-shipped order, adding a duplicate item to a basket.
 *
 * Maps to HTTP 422 Unprocessable Entity at the infrastructure boundary.
 */
final class BusinessRuleViolationException extends DomainException
{
    /**
     * Creates an exception for a named rule violation.
     *
     * @param string $rule    The name of the violated rule (for logging/tracing)
     * @param string $message Human-readable explanation of the violation
     */
    public static function forRule(string $rule, string $message): self
    {
        return new self(sprintf('[%s] %s', $rule, $message));
    }
}
