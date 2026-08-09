<?php

declare(strict_types=1);

namespace App\Navigating\EventSubscriber;

use App\Navigating\Service\Navigation\Persistence\NavigationEntityInvariantService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

final readonly class NavigationEntityInvariantSubscriber implements EventSubscriber
{
    private NavigationEntityInvariantService $invariants;

    /** @param NavigationEntityInvariantService|array<string, mixed> $invariants */
    public function __construct(NavigationEntityInvariantService|array $invariants = [])
    {
        $this->invariants = $invariants instanceof NavigationEntityInvariantService
            ? $invariants
            : new NavigationEntityInvariantService($invariants);
    }

    public function getSubscribedEvents(): array
    {
        return [Events::prePersist, Events::preUpdate];
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->invariants->validate($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->invariants->validate($args->getObject());
    }
}
