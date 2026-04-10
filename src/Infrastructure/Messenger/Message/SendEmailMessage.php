<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Message;

/**
 * Async Messenger message for sending a transactional email.
 *
 * Dispatched to the `async_high_priority` transport so that email
 * notifications are processed before lower-priority background tasks.
 *
 * This message is technology-agnostic: the handler decides which
 * Symfony Mailer transport to use. The message itself carries only
 * the data needed to compose and address the email.
 *
 * @psalm-immutable
 */
final readonly class SendEmailMessage
{
    /**
     * @param non-empty-string      $recipientEmail Destination email address
     * @param non-empty-string      $recipientName  Display name of the recipient
     * @param non-empty-string      $subject        Email subject line
     * @param non-empty-string      $template       Twig template path relative to templates/email/
     * @param array<string, mixed>  $context        Variables passed to the Twig template
     * @param non-empty-string|null $replyTo        Optional reply-to address
     */
    public function __construct(
        public string $recipientEmail,
        public string $recipientName,
        public string $subject,
        public string $template,
        public array $context = [],
        public ?string $replyTo = null,
    ) {
    }
}
