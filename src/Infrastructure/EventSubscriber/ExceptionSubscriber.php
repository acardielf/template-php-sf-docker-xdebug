<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSubscriber;

use App\Application\Exception\ApplicationException;
use App\Application\Exception\UnauthorizedException;
use App\Application\Exception\ValidationException;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\DuplicateEntityException;
use App\Domain\Exception\EntityNotFoundException;
use App\Infrastructure\Exception\InfrastructureException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Translates domain, application, and infrastructure exceptions into JSON HTTP responses.
 *
 * This subscriber is the single point where the exception hierarchy from all three
 * layers is mapped to appropriate HTTP status codes. This keeps error handling logic
 * out of individual controllers and ensures consistent response envelopes across the API.
 *
 * Mapping:
 *   Domain:
 *     EntityNotFoundException          → 404 Not Found
 *     DuplicateEntityException         → 409 Conflict
 *     BusinessRuleViolationException   → 422 Unprocessable Entity
 *     InvalidValueObjectException      → 422 Unprocessable Entity
 *     DomainException (catch-all)      → 400 Bad Request
 *
 *   Application:
 *     UnauthorizedException            → 403 Forbidden
 *     ValidationException              → 422 Unprocessable Entity (with violation details)
 *     ApplicationException (catch-all) → 400 Bad Request
 *
 *   Infrastructure:
 *     InfrastructureException          → 500 Internal Server Error (sanitized message)
 *
 *   Unhandled:
 *     Any other Throwable              → 500 Internal Server Error (generic message)
 */
final class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debug = false,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    /**
     * Handles the kernel exception event and sets a JSON response.
     *
     * Logs all exceptions with appropriate severity before translating them.
     * Infrastructure and unhandled exceptions are logged as ERROR with full trace.
     * Domain and application exceptions are logged as WARNING (expected failures).
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Only handle requests that expect JSON
        $request = $event->getRequest();
        $acceptsJson = str_contains($request->headers->get('Accept', ''), 'application/json')
            || str_starts_with($request->getPathInfo(), '/api');

        if (! $acceptsJson) {
            return;
        }

        [$statusCode, $body] = $this->buildResponse($exception);

        $this->log($exception, $statusCode);

        $event->setResponse(new JsonResponse($body, $statusCode));
    }

    /**
     * Maps an exception to an HTTP status code and response body.
     *
     * @param Throwable $exception The exception to translate
     *
     * @return array{int, array<string, mixed>} Tuple of [statusCode, responseBody]
     */
    private function buildResponse(Throwable $exception): array
    {
        return match (true) {
            // --- Domain layer ---
            $exception instanceof EntityNotFoundException => [
                Response::HTTP_NOT_FOUND,
                $this->body('not_found', $exception->getMessage()),
            ],
            $exception instanceof DuplicateEntityException => [
                Response::HTTP_CONFLICT,
                $this->body('conflict', $exception->getMessage()),
            ],
            $exception instanceof BusinessRuleViolationException => [
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $this->body('business_rule_violation', $exception->getMessage()),
            ],
            $exception instanceof DomainException => [
                Response::HTTP_BAD_REQUEST,
                $this->body('domain_error', $exception->getMessage()),
            ],

            // --- Application layer ---
            $exception instanceof UnauthorizedException => [
                Response::HTTP_FORBIDDEN,
                $this->body('forbidden', $exception->getMessage()),
            ],
            $exception instanceof ValidationException => [
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $this->body('validation_error', $exception->getMessage(), ['violations' => $exception->getViolations()]),
            ],
            $exception instanceof ApplicationException => [
                Response::HTTP_BAD_REQUEST,
                $this->body('application_error', $exception->getMessage()),
            ],

            // --- Infrastructure layer — NEVER expose internals to the client ---
            $exception instanceof InfrastructureException => [
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $this->body('server_error', 'An internal error occurred. Please try again later.'),
            ],

            // --- Unhandled fallback ---
            default => [
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $this->body('server_error', $this->debug ? $exception->getMessage() : 'An unexpected error occurred.'),
            ],
        };
    }

    /**
     * Builds a standardized JSON error body.
     *
     * @param string               $code    Machine-readable error code for API consumers
     * @param string               $message Human-readable message
     * @param array<string, mixed> $extra   Optional extra fields (e.g. violations)
     *
     * @return array<string, mixed>
     */
    private function body(string $code, string $message, array $extra = []): array
    {
        return array_merge(['error' => $code, 'message' => $message], $extra);
    }

    /**
     * Logs the exception with appropriate severity and context.
     *
     * @param Throwable $exception  The exception to log
     * @param int       $statusCode The resolved HTTP status code
     */
    private function log(Throwable $exception, int $statusCode): void
    {
        $context = [
            'exception_class' => $exception::class,
            'status_code' => $statusCode,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        if ($statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            $this->logger->error($exception->getMessage(), $context);
        } else {
            $this->logger->warning($exception->getMessage(), $context);
        }
    }
}
