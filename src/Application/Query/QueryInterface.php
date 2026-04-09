<?php

declare(strict_types=1);

namespace App\Application\Query;

/**
 * Marker interface for all application queries.
 *
 * Queries represent a request for information and must never change state.
 * They follow the Query side of CQRS and should return a read model or DTO.
 *
 * Queries should be named as questions: GetUserById, ListActiveOrders, FindProductsByCategory.
 */
interface QueryInterface
{
}
