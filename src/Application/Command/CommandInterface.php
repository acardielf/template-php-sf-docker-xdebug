<?php

declare(strict_types=1);

namespace App\Application\Command;

/**
 * Marker interface for all application commands.
 *
 * Commands represent an intent to change the state of the application.
 * They are handled by a corresponding CommandHandler and may dispatch
 * domain events as a side effect.
 *
 * Commands should be named in the imperative: CreateUser, UpdateOrder, DeleteAccount.
 */
interface CommandInterface
{
}
