<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\Uuid;
use DateTimeImmutable;

/**
 * Base class for all domain events.
 *
 * Domain events represent something that happened in the domain.
 * They are immutable and carry only the data relevant to the event.
 *
 * @psalm-immutable
 */
abstract readonly class DomainEvent
{
    /** Unique identifier for this event instance */
    private string $eventId;

    /** Timestamp when the event occurred */
    private DateTimeImmutable $occurredOn;

    public function __construct()
    {
        $this->eventId = (string) Uuid::generate();
        $this->occurredOn = new DateTimeImmutable();
    }

    /**
     * Returns the unique identifier for this event instance.
     *
     * @return non-empty-string
     */
    public function getEventId(): string
    {
        return $this->eventId;
    }

    /**
     * Returns the timestamp when the event occurred.
     */
    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * Returns the event type name for serialization and routing.
     */
    abstract public function getEventName(): string;
}
