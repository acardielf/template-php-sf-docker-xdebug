<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Base class for typed API request objects.
 *
 * Decodes the JSON body and validates it using Symfony Validator.
 * Controllers should extend this class to get strongly typed, validated input.
 *
 * Example usage:
 * <code>
 *   public function create(CreateUserRequest $request): JsonResponse { ... }
 * </code>
 */
abstract class AbstractApiRequest
{
    /**
     * Populated from the raw JSON body.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    private ConstraintViolationListInterface $violations;

    public function __construct(
        private readonly Request $request,
        private readonly ValidatorInterface $validator
    ) {
        $this->data = $this->decodeBody();
        $this->violations = $this->validator->validate($this);
    }

    /**
     * Checks whether the request passed all validation constraints.
     */
    public function isValid(): bool
    {
        return $this->violations->count() === 0;
    }

    /**
     * Returns the list of validation constraint violations.
     */
    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }

    /**
     * Returns the raw Symfony Request object.
     */
    protected function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Decodes and returns the JSON request body.
     *
     * @return array<string, mixed>
     */
    private function decodeBody(): array
    {
        $content = $this->request->getContent();

        if ($content === '') {
            return [];
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($content, true);

        return $decoded ?? [];
    }
}
