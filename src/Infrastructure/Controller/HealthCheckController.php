<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Health check endpoint.
 *
 * Provides a simple liveness probe for container orchestration
 * and uptime monitoring tools.
 */
#[Route('/api')]
#[OA\Tag(name: 'Health')]
final class HealthCheckController extends AbstractController
{
    /**
     * Returns the current health status of the application.
     *
     * Used by load balancers and orchestrators (Kubernetes, Docker Swarm)
     * to determine if the container is ready to accept traffic.
     */
    #[Route('/health', name: 'api_health_check', methods: ['GET'])]
    #[OA\Get(
        path: '/api/health',
        summary: 'Application health check',
        description: 'Returns the current health status of the application. Use this endpoint for liveness and readiness probes.',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Application is healthy',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2026-01-01T00:00:00+00:00'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }
}
