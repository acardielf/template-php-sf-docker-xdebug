<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Event\DomainEvent;

/**
 * Base class for all aggregate roots.
 *
 * Provides the domain-events pile pattern: aggregates record events internally
 * via recordEvent(), and the Application layer collects them via pullDomainEvents()
 * after persisting the aggregate, then dispatches them to the event bus.
 *
 * Usage in an aggregate:
 *   $this->recordEvent(new SomethingHappened($this->id, ...));
 *
 * Usage in a command handler:
 *   $this->repository->save($aggregate);
 *   foreach ($aggregate->pullDomainEvents() as $event) {
 *       $this->eventBus->dispatch($event);
 *   }
 */
abstract class AggregateRoot
{
    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    /**
     * Records a domain event to be dispatched after the aggregate is persisted.
     */
    protected function recordEvent(DomainEvent $domainEvent): void
    {
        $this->domainEvents[] = $domainEvent;
    }

    /**
     * Returns all recorded domain events and clears the internal pile.
     * Call this once after saving the aggregate.
     *
     * @return list<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
