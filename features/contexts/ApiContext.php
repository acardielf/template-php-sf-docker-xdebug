<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use FriendsOfBehat\SymfonyExtension\Mink\MinkParameters;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Behat context for API testing.
 *
 * Provides step definitions to make HTTP requests and assert responses
 * without a full browser. Suitable for testing REST API endpoints.
 */
final class ApiContext implements Context
{
    private ?Response $lastResponse = null;

    public function __construct(
        private readonly KernelInterface $kernel
    ) {
    }

    /**
     * @When I send a GET request to :path
     */
    public function iSendAGetRequest(string $path): void
    {
        $this->lastResponse = $this->kernel->handle(
            Request::create($path, 'GET')
        );
    }

    /**
     * @When I send a POST request to :path with:
     */
    public function iSendAPostRequestWith(string $path, PyStringNode $body): void
    {
        $request = Request::create(
            $path,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body->getRaw()
        );

        $this->lastResponse = $this->kernel->handle($request);
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe(int $statusCode): void
    {
        if ($this->lastResponse === null) {
            throw new \RuntimeException('No response available. Did you send a request first?');
        }

        if ($this->lastResponse->getStatusCode() !== $statusCode) {
            throw new \RuntimeException(sprintf(
                'Expected status code %d, got %d. Response body: %s',
                $statusCode,
                $this->lastResponse->getStatusCode(),
                $this->lastResponse->getContent()
            ));
        }
    }

    /**
     * @Then the response should contain JSON key :key with value :value
     */
    public function theResponseShouldContainJsonKeyWithValue(string $key, string $value): void
    {
        if ($this->lastResponse === null) {
            throw new \RuntimeException('No response available. Did you send a request first?');
        }

        /** @var array<string, mixed> $data */
        $data = json_decode((string) $this->lastResponse->getContent(), true);

        if (! array_key_exists($key, $data)) {
            throw new \RuntimeException(sprintf('Key "%s" not found in response.', $key));
        }

        if ((string) $data[$key] !== $value) {
            throw new \RuntimeException(sprintf(
                'Expected value "%s" for key "%s", got "%s".',
                $value,
                $key,
                (string) $data[$key]
            ));
        }
    }
}
