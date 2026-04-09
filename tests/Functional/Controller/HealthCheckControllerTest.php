<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers \App\Infrastructure\Controller\HealthCheckController
 */
final class HealthCheckControllerTest extends WebTestCase
{
    #[Test]
    public function itReturnsHealthStatusOk(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $response = $client->getResponse();

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        /** @var array<string, mixed> $data */
        $data = json_decode((string) $response->getContent(), true);

        self::assertSame('ok', $data['status']);
        self::assertArrayHasKey('timestamp', $data);
    }
}
