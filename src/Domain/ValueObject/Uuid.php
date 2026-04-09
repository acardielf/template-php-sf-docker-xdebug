<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * UUID value object.
 *
 * Represents a universally unique identifier.
 * Immutable by design — create new instances instead of mutating.
 *
 * @psalm-immutable
 */
final class Uuid implements Stringable
{
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /**
     * @param non-empty-string $value The UUID string value
     *
     * @throws InvalidArgumentException When the value is not a valid UUID
     */
    private function __construct(private readonly string $value)
    {
        $this->validate($value);
    }

    /**
     * Creates a new UUID value object from a string.
     *
     * @param non-empty-string $value The UUID string
     *
     * @throws InvalidArgumentException When the value is not a valid UUID
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Generates a new random UUID v4.
     */
    public static function generate(): self
    {
        return new self(sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        ));
    }

    /**
     * Returns the UUID as a string.
     *
     * @return non-empty-string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Checks equality with another UUID.
     */
    public function equals(self $other): bool
    {
        return strtolower($this->value) === strtolower($other->value);
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Validates that the value conforms to UUID format.
     *
     * @param non-empty-string $value
     *
     * @throws InvalidArgumentException When the value is not a valid UUID
     */
    private function validate(string $value): void
    {
        if (! preg_match(self::UUID_PATTERN, $value)) {
            throw new InvalidArgumentException(
                sprintf('"%s" is not a valid UUID.', $value)
            );
        }
    }
}
