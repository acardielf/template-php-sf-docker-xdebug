<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Uuid;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Domain\ValueObject\Uuid
 */
#[CoversClass(Uuid::class)]
final class UuidTest extends TestCase
{
    #[Test]
    public function itGeneratesAValidUuid(): void
    {
        $uuid = Uuid::generate();

        self::assertNotEmpty((string) $uuid);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            (string) $uuid
        );
    }

    #[Test]
    public function itCreatesFromValidString(): void
    {
        $uuidString = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = Uuid::fromString($uuidString);

        self::assertSame($uuidString, $uuid->getValue());
        self::assertSame($uuidString, (string) $uuid);
    }

    #[Test]
    #[DataProvider('invalidUuidProvider')]
    public function itRejectsInvalidUuids(string $invalid): void
    {
        $this->expectException(InvalidArgumentException::class);

        Uuid::fromString($invalid);
    }

    #[Test]
    public function itChecksEqualityCorrectly(): void
    {
        $uuidString = '550e8400-e29b-41d4-a716-446655440000';
        $a = Uuid::fromString($uuidString);
        $b = Uuid::fromString($uuidString);
        $c = Uuid::generate();

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    #[Test]
    public function itGeneratesUniqueValues(): void
    {
        $a = Uuid::generate();
        $b = Uuid::generate();

        self::assertFalse($a->equals($b));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidUuidProvider(): array
    {
        return [
            'empty string' => [''],
            'too short' => ['550e8400-e29b-41d4-a716'],
            'no dashes' => ['550e8400e29b41d4a716446655440000'],
            'invalid chars' => ['gggggggg-gggg-gggg-gggg-gggggggggggg'],
        ];
    }
}
