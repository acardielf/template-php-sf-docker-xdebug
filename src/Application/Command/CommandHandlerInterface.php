<?php

declare(strict_types=1);

namespace App\Application\Command;

/**
 * Marker interface for all application command handlers.
 *
 * Each command handler is responsible for handling exactly one command type.
 * Handlers orchestrate domain objects to fulfill the command's intent.
 * They should not contain business logic — that belongs in the domain layer.
 *
 * @template TCommand of CommandInterface
 */
interface CommandHandlerInterface
{
    /**
     * Handles the given command.
     *
     * @param CommandInterface $command The command to handle
     */
    public function __invoke(CommandInterface $command): void;
}
