<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger;

use App\Application\Query\QueryInterface;
use App\Application\Service\QueryBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

/**
 * Symfony Messenger adapter for the query bus.
 *
 * Wraps Symfony's MessageBus and translates between the application
 * port (QueryBusInterface) and the framework adapter.
 */
final class SymfonyQueryBus implements QueryBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $queryBus
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @throws Throwable When query handling fails
     */
    public function ask(QueryInterface $query): mixed
    {
        try {
            $envelope = $this->queryBus->dispatch($query);
            /** @var HandledStamp|null $handledStamp */
            $handledStamp = $envelope->last(HandledStamp::class);

            if ($handledStamp === null) {
                return null;
            }

            return $handledStamp->getResult();
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof Throwable) {
                throw $previous;
            }

            throw $e;
        }
    }
}
