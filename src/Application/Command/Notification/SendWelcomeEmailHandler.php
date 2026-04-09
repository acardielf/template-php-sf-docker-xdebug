<?php

declare(strict_types=1);

namespace App\Application\Command\Notification;

use App\Application\Command\CommandInterface;
use App\Application\Command\CommandHandlerInterface;
use App\Infrastructure\Exception\MessagingException;
use App\Infrastructure\Messenger\Message\SendEmailMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Handles SendWelcomeEmailCommand by enqueuing an async email message.
 *
 * This handler lives in the Application layer because it orchestrates
 * a use-case. The actual email delivery is delegated to the Infrastructure
 * layer (SendEmailMessageHandler) via the async message bus.
 *
 * This separation means: if the broker is down, the command fails fast here
 * (MessagingException) rather than blocking the HTTP request waiting for
 * the SMTP server.
 */
#[AsMessageHandler]
final class SendWelcomeEmailHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly MessageBusInterface $asyncBus,
    ) {
    }

    /**
     * Enqueues a welcome email for asynchronous delivery.
     *
     * @param SendWelcomeEmailCommand $command The command carrying recipient details
     *
     * @throws MessagingException When the message cannot be dispatched to the broker
     */
    public function __invoke(CommandInterface $command): void
    {
        /** @var SendWelcomeEmailCommand $command */
        $message = new SendEmailMessage(
            recipientEmail: $command->email,
            recipientName: $command->name,
            subject: 'Welcome! Your account is ready.',
            template: 'welcome.html.twig',
            context: [
                'user_name' => $command->name,
                'user_id' => $command->userId,
            ],
        );

        try {
            $this->asyncBus->dispatch($message);
        } catch (ExceptionInterface $e) {
            throw MessagingException::failedToDispatch(SendEmailMessage::class, $e);
        }
    }
}
