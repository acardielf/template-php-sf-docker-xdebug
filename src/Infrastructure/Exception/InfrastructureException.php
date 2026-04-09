<?php

declare(strict_types=1);

namespace App\Infrastructure\Exception;

use RuntimeException;

/**
 * Base class for all infrastructure layer exceptions.
 *
 * Infrastructure exceptions wrap technical failures that originate in
 * external systems: the database, cache, message broker, mail server,
 * third-party APIs, etc.
 *
 * They are always the result of an adapter failure, never a domain invariant.
 * All concrete infrastructure exceptions must extend this class so they can
 * be caught uniformly at the application boundary and translated to HTTP 500.
 */
abstract class InfrastructureException extends RuntimeException
{
}
