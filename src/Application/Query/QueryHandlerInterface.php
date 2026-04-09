<?php

declare(strict_types=1);

namespace App\Application\Query;

/**
 * Marker interface for all application query handlers.
 *
 * Query handlers retrieve and return data without modifying application state.
 * They may bypass the domain model and query the read store directly for performance.
 *
 * @template TQuery of QueryInterface
 * @template TResult
 */
interface QueryHandlerInterface
{
    /**
     * Handles the given query and returns the result.
     *
     * @param QueryInterface $query The query to handle
     *
     * @return mixed The query result — a DTO, collection, or scalar value
     */
    public function __invoke(QueryInterface $query): mixed;
}
