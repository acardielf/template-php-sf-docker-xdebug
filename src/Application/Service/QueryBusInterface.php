<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Query\QueryInterface;
use Throwable;

/**
 * Query bus port (interface).
 *
 * Dispatches queries to their corresponding handlers and returns the result.
 * The concrete implementation lives in the Infrastructure layer
 * and adapts the framework's message bus.
 */
interface QueryBusInterface
{
    /**
     * Dispatches a query to its registered handler and returns the result.
     *
     * @param QueryInterface $query The query to dispatch
     *
     * @return mixed The query result
     *
     * @throws Throwable When query handling fails
     */
    public function ask(QueryInterface $query): mixed;
}
