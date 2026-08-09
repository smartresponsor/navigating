<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Persistence;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;

final readonly class NavigationEntityInvariantService
{
    /** @param array<string, mixed> $navigationConfig */
    public function __construct(
        private array $navigationConfig = [],
    ) {
    }

    public function validate(object $entity): void
    {
        if ($entity instanceof NavigationMenu) {
            $this->requireKey($entity->getMenuKey(), 'Navigation menu key', 160);
            $this->requireSlug($entity->getSlug(), 'Navigation menu slug', 180);
            $this->requireStringLength($entity->getLabel(), 'Navigation menu label', 140, true);
            $this->requireLocation($entity->getLocation());
            $this->requireType($entity->getType(), 'Navigation menu type', 60);

            return;
        }

        if (!$entity instanceof NavigationItem) {
            return;
        }

        $menu = $entity->getMenu();
        if (null === $menu) {
            throw new \DomainException('Navigation item must belong to a navigation menu.');
        }

        $parent = $entity->getParent();
        if (null !== $parent && $parent->getMenu() !== $menu) {
            throw new \DomainException('Navigation item parent must belong to the same menu.');
        }

        $visited = [spl_object_id($entity) => true];
        for ($ancestor = $parent; null !== $ancestor; $ancestor = $ancestor->getParent()) {
            $objectId = spl_object_id($ancestor);
            if (isset($visited[$objectId])) {
                throw new \DomainException('Navigation item hierarchy cannot contain cycles.');
            }
            $visited[$objectId] = true;
        }

        $this->requireKey($entity->getNavigationKey(), 'Navigation item key', 160);
        if (null !== $entity->getSlug()) {
            $this->requireSlug($entity->getSlug(), 'Navigation item slug', 180);
        }
        $this->requireStringLength($entity->getLabel(), 'Navigation item label', 140, true);
        $this->requireType($entity->getType(), 'Navigation item type', 40);
        $this->requireOperation($entity->getOperation());
        $this->requireNullableStringLength($entity->getRouteName(), 'Navigation item route name', 180);
        $this->requireNullableStringLength($entity->getPath(), 'Navigation item path', 512);
        $this->requireNullableStringLength($entity->getIcon(), 'Navigation item icon', 80);
        $this->requireNullableStringLength($entity->getBadge(), 'Navigation item badge', 80);

        $hasRoute = null !== $entity->getRouteName();
        $hasPath = null !== $entity->getPath();
        if ($hasRoute && $hasPath) {
            throw new \DomainException('Navigation item must use either a route target or a path target, never both.');
        }
        if (!$hasRoute && [] !== $entity->getRouteParameters()) {
            throw new \DomainException('Navigation item route parameters require a route target.');
        }
    }

    private function requireNonEmpty(string $value, string $message): void
    {
        if ('' === trim($value)) {
            throw new \DomainException($message);
        }
    }

    private function requireKey(string $value, string $field, int $maxLength): void
    {
        $this->requireStringLength($value, $field, $maxLength, true);

        if (1 !== preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $value)) {
            throw new \DomainException($field.' must be a lowercase navigation token using letters, digits, dots, underscores or hyphens.');
        }
    }

    private function requireSlug(string $value, string $field, int $maxLength): void
    {
        $this->requireStringLength($value, $field, $maxLength, true);

        if (1 !== preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            throw new \DomainException($field.' must use lowercase kebab-case.');
        }
    }

    private function requireType(string $value, string $field, int $maxLength): void
    {
        $this->requireStringLength($value, $field, $maxLength, true);

        if (1 !== preg_match('/^[a-z][a-z0-9._-]*$/', $value)) {
            throw new \DomainException($field.' must be a lowercase navigation type token.');
        }
    }

    private function requireOperation(string $operation): void
    {
        $this->requireStringLength($operation, 'Navigation item operation', 60, true);

        if (1 !== preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/', $operation)) {
            throw new \DomainException('Navigation item operation must use lowercase snake_case.');
        }
    }

    private function requireLocation(string $location): void
    {
        $this->requireStringLength($location, 'Navigation menu location', 120, true);

        $locations = $this->navigationConfig['shell_locations'] ?? [];
        if (!is_array($locations) || !array_key_exists($location, $locations)) {
            throw new \DomainException(sprintf('Navigation menu location "%s" is not registered in shell_locations.', $location));
        }
    }

    private function requireNullableStringLength(?string $value, string $field, int $maxLength): void
    {
        if (null === $value) {
            return;
        }

        $this->requireStringLength($value, $field, $maxLength, false);
    }

    private function requireStringLength(string $value, string $field, int $maxLength, bool $required): void
    {
        if ($required) {
            $this->requireNonEmpty($value, $field.' cannot be empty.');
        }

        if ($this->characterLength($value) > $maxLength) {
            throw new \DomainException(sprintf('%s cannot exceed %d characters.', $field, $maxLength));
        }
    }

    private function characterLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        $matched = preg_match_all('/./us', $value, $matches);
        if (false === $matched) {
            throw new \DomainException('Navigation text must be valid UTF-8.');
        }

        return $matched;
    }
}
