<?php

declare(strict_types=1);

namespace App\Navigating\EventSubscriber;

use App\Navigating\Entity\NavigationItemEntity;
use App\Navigating\Entity\NavigationMenuEntity;
use App\Navigating\Service\Cache\NavigationConfigCacheService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

final class NavigationConfigCacheInvalidationSubscriber implements EventSubscriber
{
    private bool $dirty = false;

    public function __construct(
        private readonly NavigationConfigCacheService $cache,
    ) {
    }

    /** @return list<string> */
    public function getSubscribedEvents(): array
    {
        return [Events::postPersist, Events::postUpdate, Events::postRemove, Events::postFlush];
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $this->markDirty($event->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $this->markDirty($event->getObject());
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $this->markDirty($event->getObject());
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if (!$this->dirty) {
            return;
        }

        $this->dirty = false;

        if ($event->getObjectManager()->getConnection()->getTransactionNestingLevel() > 0) {
            return;
        }

        $this->cache->invalidate();
    }

    private function markDirty(object $entity): void
    {
        if ($entity instanceof NavigationMenuEntity || $entity instanceof NavigationItemEntity) {
            $this->dirty = true;
        }
    }
}
