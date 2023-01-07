<?php

declare(strict_types=1);

namespace App\EventSubscriber\Entry;

use App\Event\Entry\EntryCreatedEvent;
use App\Message\ActivityPub\Outbox\CreateMessage;
use App\Message\EntryEmbedMessage;
use App\Message\Notification\EntryCreatedNotificationMessage;
use App\Service\DomainManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EntryCreateSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly MessageBusInterface $bus, private readonly DomainManager $manager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntryCreatedEvent::class => 'onEntryCreated',
        ];
    }

    public function onEntryCreated(EntryCreatedEvent $event): void
    {
        $this->manager->extract($event->entry);
        $this->bus->dispatch(new EntryEmbedMessage($event->entry->getId()));
        $this->bus->dispatch(new EntryCreatedNotificationMessage($event->entry->getId()));

        if (!$event->entry->apId) {
            $this->bus->dispatch(new CreateMessage($event->entry->getId(), get_class($event->entry)));
        }
    }
}
