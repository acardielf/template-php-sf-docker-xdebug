<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Handler;

use App\Infrastructure\Exception\MailerException;
use App\Infrastructure\Messenger\Message\SendEmailMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

/**
 * Handles SendEmailMessage by composing and delivering a Twig-templated email.
 *
 * This handler runs asynchronously in the `messenger-worker` container.
 * It wraps all Symfony Mailer exceptions in MailerException so that
 * Messenger's retry strategy can handle transient failures transparently.
 *
 * On permanent failure (after max retries), the message is routed to the
 * `failed` transport for manual inspection.
 */
#[AsMessageHandler(fromTransport: 'async_high_priority')]
final class SendEmailMessageHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $senderEmail,
        private readonly string $senderName,
    ) {
    }

    /**
     * Composes a Twig-templated email from the message payload and sends it.
     *
     * @param SendEmailMessage $message The message carrying recipient, subject, template, and context
     *
     * @throws MailerException When the mailer transport fails to deliver the email
     */
    public function __invoke(SendEmailMessage $message): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($message->recipientEmail, $message->recipientName))
            ->subject($message->subject)
            ->htmlTemplate(sprintf('email/%s', $message->template))
            ->context($message->context);

        if ($message->replyTo !== null) {
            $email->replyTo($message->replyTo);
        }

        try {
            $this->mailer->send($email);

            $this->logger->info('Email sent successfully.', [
                'recipient' => $message->recipientEmail,
                'subject' => $message->subject,
                'template' => $message->template,
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send email.', [
                'recipient' => $message->recipientEmail,
                'subject' => $message->subject,
                'error' => $e->getMessage(),
            ]);

            throw MailerException::failedToSend($message->recipientEmail, $e);
        }
    }
}
