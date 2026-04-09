<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Exception\EntityNotFoundException;
use App\Infrastructure\Http\Request\AbstractApiRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Base controller for all API endpoints.
 *
 * Provides helpers for building consistent JSON responses and
 * translating domain exceptions to HTTP responses.
 */
abstract class AbstractApiController extends AbstractController
{
    /**
     * Returns a 201 Created response with the given data.
     *
     * @param array<string, mixed> $data
     */
    protected function created(array $data = []): JsonResponse
    {
        return $this->json($data, Response::HTTP_CREATED);
    }

    /**
     * Returns a 204 No Content response.
     */
    protected function noContent(): JsonResponse
    {
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Returns a 404 Not Found response from a domain exception.
     */
    protected function notFound(EntityNotFoundException $e): JsonResponse
    {
        return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
    }

    /**
     * Returns a 422 Unprocessable Entity response with validation errors.
     *
     * Normalizes the violation list into a structured error body.
     */
    protected function validationError(AbstractApiRequest $request): JsonResponse
    {
        /** @var array<int, array<string, string>> $errors */
        $errors = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($request->getViolations() as $violation) {
            $errors[] = [
                'field' => (string) $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        return $this->json(
            ['errors' => $errors],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
