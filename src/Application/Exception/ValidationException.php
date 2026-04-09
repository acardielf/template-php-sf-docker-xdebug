<?php

declare(strict_types=1);

namespace App\Application\Exception;

/**
 * Thrown when a command or query carries semantically invalid data.
 *
 * This is distinct from HTTP-level validation (handled by request objects).
 * Use this when cross-field or cross-aggregate validation fails inside a handler
 * before any domain object is touched.
 *
 * Maps to HTTP 422 Unprocessable Entity at the infrastructure boundary.
 */
final class ValidationException extends ApplicationException
{
    /**
     * @var array<string, string> $violations Field → message map of validation failures
     */
    private readonly array $violations;

    /**
     * @param array<string, string> $violations Field → message map
     */
    public function __construct(array $violations)
    {
        $this->violations = $violations;

        parent::__construct(
            sprintf('Validation failed: %s', implode(', ', array_map(
                static fn (string $field, string $message): string => sprintf('%s — %s', $field, $message),
                array_keys($violations),
                $violations
            )))
        );
    }

    /**
     * Returns the map of field → error message for each failed constraint.
     *
     * @return array<string, string>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
