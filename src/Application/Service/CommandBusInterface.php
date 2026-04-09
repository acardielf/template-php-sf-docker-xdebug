<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Command\CommandInterface;

/**
 * Command bus port (interface).
 *
 * Dispatches commands to their corresponding handlers.
 * The concrete implementation lives in the Infrastructure layer
 * and adapts the framework's message bus.
 */
interface CommandBusInterface
{
    /**
     * Dispatches a command to its registered handler.
     *
     * @param CommandInterface $command The command to dispatch
     *
     * @throws \Throwable When command handling fails
     */
    public function dispatch(CommandInterface $command): void;
}
