<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Infrastructure\Controller\HealthCheckController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(HealthCheckController::class)]
final class HealthCheckControllerTest extends WebTestCase
{
    #[Test]
    public function itReturnsHealthStatusOk(): void
    {
        $kernelBrowser = self::createClient();
        $kernelBrowser->request(Request::METHOD_GET, '/api/health');

        $response = $kernelBrowser->getResponse();

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        /** @var array<string, mixed> $data */
        $data = json_decode((string) $response->getContent(), true);

        self::assertSame('ok', $data['status']);
        self::assertArrayHasKey('timestamp', $data);
    }
}
