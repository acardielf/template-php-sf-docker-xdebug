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
final readonly class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $debug = false,
    ) {
    }

    /**
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
    public function onKernelException(ExceptionEvent $exceptionEvent): void
    {
        $throwable = $exceptionEvent->getThrowable();

        // Only handle requests that expect JSON
        $request = $exceptionEvent->getRequest();
        $acceptsJson = str_contains((string) $request->headers->get('Accept', ''), 'application/json')
            || str_starts_with($request->getPathInfo(), '/api');

        if (!$acceptsJson) {
            return;
        }

        [$statusCode, $body] = $this->buildResponse($throwable);

        $this->log($throwable, $statusCode);

        $exceptionEvent->setResponse(new JsonResponse($body, $statusCode));
    }

    /**
     * Maps an exception to an HTTP status code and response body.
     *
     * @param Throwable $throwable The exception to translate
     *
     * @return array{int, array<string, mixed>} Tuple of [statusCode, responseBody]
     */
    private function buildResponse(Throwable $throwable): array
    {
        return match (true) {
            // --- Domain layer ---
            $throwable instanceof EntityNotFoundException => [
                Response::HTTP_NOT_FOUND,
                $this->body('not_found', $throwable->getMessage()),
            ],
            $throwable instanceof DuplicateEntityException => [
                Response::HTTP_CONFLICT,
                $this->body('conflict', $throwable->getMessage()),
            ],
            $throwable instanceof BusinessRuleViolationException => [
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $this->body('business_rule_violation', $throwable->getMessage()),
            ],
            $throwable instanceof DomainException => [
                Response::HTTP_BAD_REQUEST,
                $this->body('domain_error', $throwable->getMessage()),
            ],

            // --- Application layer ---
            $throwable instanceof UnauthorizedException => [
                Response::HTTP_FORBIDDEN,
                $this->body('forbidden', $throwable->getMessage()),
            ],
            $throwable instanceof ValidationException => [
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $this->body('validation_error', $throwable->getMessage(), ['violations' => $throwable->getViolations()]),
            ],
            $throwable instanceof ApplicationException => [
                Response::HTTP_BAD_REQUEST,
                $this->body('application_error', $throwable->getMessage()),
            ],

            // --- Infrastructure layer — NEVER expose internals to the client ---
            $throwable instanceof InfrastructureException => [
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $this->body('server_error', 'An internal error occurred. Please try again later.'),
            ],

            // --- Unhandled fallback ---
            default => [
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $this->body('server_error', $this->debug ? $throwable->getMessage() : 'An unexpected error occurred.'),
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
     * @param Throwable $throwable  The exception to log
     * @param int       $statusCode The resolved HTTP status code
     */
    private function log(Throwable $throwable, int $statusCode): void
    {
        $context = [
            'exception_class' => $throwable::class,
            'status_code' => $statusCode,
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ];

        if ($statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            $this->logger->error($throwable->getMessage(), $context);
        } else {
            $this->logger->warning($throwable->getMessage(), $context);
        }
    }
}
