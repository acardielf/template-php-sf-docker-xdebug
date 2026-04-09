<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

/**
 * Base class for all domain exceptions.
 *
 * All domain-specific exceptions should extend this class
 * to allow catching domain errors at the application layer boundary.
 */
abstract class DomainException extends RuntimeException
{
}
