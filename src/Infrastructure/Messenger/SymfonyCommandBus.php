<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger;

use App\Application\Command\CommandInterface;
use App\Application\Service\CommandBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

/**
 * Symfony Messenger adapter for the command bus.
 *
 * Wraps Symfony's MessageBus and translates between the application
 * port (CommandBusInterface) and the framework adapter.
 */
final class SymfonyCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * Unwraps HandlerFailedException to expose the original domain exception.
     *
     * @throws Throwable When command handling fails
     */
    public function dispatch(CommandInterface $command): void
    {
        try {
            $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof Throwable) {
                throw $previous;
            }

            throw $e;
        }
    }
}
