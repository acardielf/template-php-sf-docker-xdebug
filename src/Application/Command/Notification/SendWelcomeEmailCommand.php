<?php

declare(strict_types=1);

namespace App\Application\Command\Notification;

use App\Application\Command\CommandInterface;

/**
 * Command to send a welcome email to a newly registered user.
 *
 * Dispatching this command enqueues an async email via the message broker.
 * The command handler does not send the email directly — it delegates to
 * the infrastructure layer through a Messenger message, keeping
 * the application layer decoupled from the mailing transport.
 *
 * @psalm-immutable
 */
final readonly class SendWelcomeEmailCommand implements CommandInterface
{
    /**
     * @param non-empty-string $userId The identifier of the registered user
     * @param non-empty-string $email  The recipient email address
     * @param non-empty-string $name   The recipient's display name
     */
    public function __construct(
        public string $userId,
        public string $email,
        public string $name,
    ) {
    }
}
