<?php

declare(strict_types=1);

namespace App\Application\Exception;

use RuntimeException;

/**
 * Base class for all application layer exceptions.
 *
 * Application exceptions represent failures in orchestrating domain objects
 * to fulfil a use case. They are distinct from domain exceptions (which express
 * broken invariants) and infrastructure exceptions (which wrap technical failures).
 *
 * All application exceptions should extend this class so that the infrastructure
 * layer (exception subscriber) can catch and translate them uniformly.
 */
abstract class ApplicationException extends RuntimeException
{
}
