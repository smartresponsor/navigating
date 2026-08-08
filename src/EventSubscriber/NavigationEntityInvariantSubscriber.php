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
    /** @param array<string, mixed> $navigationConfig */
    public function __construct(
        private readonly array $navigationConfig = [],
    ) {
    }

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
            $this->requireKey($entity->getMenuKey(), 'Navigation menu key');
            $this->requireSlug($entity->getSlug(), 'Navigation menu slug');
            $this->requireNonEmpty($entity->getLabel(), 'Navigation menu label cannot be empty.');
            $this->requireLocation($entity->getLocation());
            $this->requireType($entity->getType(), 'Navigation menu type');

            return;
        }

        if (!$entity instanceof NavigationItem) {
            return;
        }

        if (null === $entity->getMenu()) {
            throw new \DomainException('Navigation item must belong to a navigation menu.');
        }

        $this->requireKey($entity->getNavigationKey(), 'Navigation item key');
        if (null !== $entity->getSlug()) {
            $this->requireSlug($entity->getSlug(), 'Navigation item slug');
        }
        $this->requireNonEmpty($entity->getLabel(), 'Navigation item label cannot be empty.');
        $this->requireType($entity->getType(), 'Navigation item type');
        $this->requireOperation($entity->getOperation());
    }

    private function requireNonEmpty(string $value, string $message): void
    {
        if ('' === trim($value)) {
            throw new \DomainException($message);
        }
    }

    private function requireKey(string $value, string $field): void
    {
        $this->requireNonEmpty($value, $field.' cannot be empty.');

        if (1 !== preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $value)) {
            throw new \DomainException($field.' must be a lowercase navigation token using letters, digits, dots, underscores or hyphens.');
        }
    }

    private function requireSlug(string $value, string $field): void
    {
        $this->requireNonEmpty($value, $field.' cannot be empty.');

        if (1 !== preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            throw new \DomainException($field.' must use lowercase kebab-case.');
        }
    }

    private function requireType(string $value, string $field): void
    {
        $this->requireNonEmpty($value, $field.' cannot be empty.');

        if (1 !== preg_match('/^[a-z][a-z0-9._-]*$/', $value)) {
            throw new \DomainException($field.' must be a lowercase navigation type token.');
        }
    }

    private function requireOperation(string $operation): void
    {
        $this->requireNonEmpty($operation, 'Navigation item operation cannot be empty.');

        if (1 !== preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/', $operation)) {
            throw new \DomainException('Navigation item operation must use lowercase snake_case.');
        }
    }

    private function requireLocation(string $location): void
    {
        $this->requireNonEmpty($location, 'Navigation menu location cannot be empty.');

        $locations = $this->navigationConfig['shell_locations'] ?? [];
        if (!is_array($locations) || !array_key_exists($location, $locations)) {
            throw new \DomainException(sprintf('Navigation menu location "%s" is not registered in shell_locations.', $location));
        }
    }
}
