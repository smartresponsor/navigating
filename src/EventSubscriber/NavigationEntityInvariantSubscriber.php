<?php

declare(strict_types=1);

namespace App\Navigating\EventSubscriber;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

final class NavigationEntityInvariantSubscriber implements EventSubscriber
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
        if ($entity instanceof NavigationMenu) {
            $this->requireNonEmpty($entity->getMenuKey(), 'Navigation menu key cannot be empty.');
            $this->requireNonEmpty($entity->getSlug(), 'Navigation menu slug cannot be empty.');
            $this->requireNonEmpty($entity->getLabel(), 'Navigation menu label cannot be empty.');
            $this->requireNonEmpty($entity->getLocation(), 'Navigation menu location cannot be empty.');
            $this->requireNonEmpty($entity->getType(), 'Navigation menu type cannot be empty.');

            return;
        }

        if (!$entity instanceof NavigationItem) {
            return;
        }

        if (null === $entity->getMenu()) {
            throw new \DomainException('Navigation item must belong to a navigation menu.');
        }

        $this->requireNonEmpty($entity->getNavigationKey(), 'Navigation item key cannot be empty.');
        $this->requireNonEmpty($entity->getLabel(), 'Navigation item label cannot be empty.');
        $this->requireNonEmpty($entity->getType(), 'Navigation item type cannot be empty.');
        $this->requireNonEmpty($entity->getOperation(), 'Navigation item operation cannot be empty.');
    }

    private function requireNonEmpty(string $value, string $message): void
    {
        if ('' === trim($value)) {
            throw new \DomainException($message);
        }
    }
}
