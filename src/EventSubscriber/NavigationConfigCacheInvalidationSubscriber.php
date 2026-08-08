<?php

declare(strict_types=1);

namespace App\Navigating\EventSubscriber;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Service\Navigation\Cache\NavigationConfigCacheService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

final readonly class NavigationConfigCacheInvalidationSubscriber implements EventSubscriber
{
    public function __construct(
        private NavigationConfigCacheService $cache,
    ) {
    }

    /** @return list<string> */
    public function getSubscribedEvents(): array
    {
        return [Events::postPersist, Events::postUpdate, Events::postRemove];
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $this->invalidateFor($event->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $this->invalidateFor($event->getObject());
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $this->invalidateFor($event->getObject());
    }

    private function invalidateFor(object $entity): void
    {
        if (!$entity instanceof NavigationMenu && !$entity instanceof NavigationItem) {
            return;
        }

        $this->cache->invalidate();
    }
}
