<?php

declare(strict_types=1);

namespace App\Navigating\EventSubscriber;

use App\Navigating\Entity\NavigationItem;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

final class NavigationItemTargetInvariantSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [Events::prePersist, Events::preUpdate];
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->validate($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->validate($args->getObject());
    }

    private function validate(object $entity): void
    {
        if (!$entity instanceof NavigationItem) {
            return;
        }

        $hasRoute = null !== $entity->getRouteName();
        $hasPath = null !== $entity->getPath();

        if ($hasRoute && $hasPath) {
            throw new \DomainException('Navigation item must use either a route target or a path target, never both.');
        }

        if (!$hasRoute && [] !== $entity->getRouteParameters()) {
            throw new \DomainException('Navigation item route parameters require a route target.');
        }
    }
}
