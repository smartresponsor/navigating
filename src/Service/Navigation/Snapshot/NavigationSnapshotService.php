<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Snapshot;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Repository\NavigationMenuRepository;
use App\Navigating\Service\Navigation\Import\NavigationConfigImportService;
use Doctrine\ORM\EntityManagerInterface;

final readonly class NavigationSnapshotService
{
    public const FORMAT = 'smartresponsor.navigation';
    public const VERSION = 1;

    public function __construct(
        private NavigationMenuRepository $menuRepository,
        private NavigationConfigImportService $importService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /** @return array<string, mixed> */
    public function create(): array
    {
        $groups = [];
        $archived = [];

        foreach ($this->menuRepository->findAll() as $menu) {
            $items = [];
            foreach ($menu->getItems() as $item) {
                $metadata = $item->getMetadata();
                if (null !== $item->getParent()) {
                    $metadata['parent_key'] = $item->getParent()?->getNavigationKey();
                }

                $itemConfig = [
                    'type' => $item->getType(),
                    'label' => $item->getLabel(),
                    'slug' => $item->getSlug(),
                    'operation' => $item->getOperation(),
                    'icon' => $item->getIcon(),
                    'badge' => $item->getBadge(),
                    'priority' => $item->getPosition(),
                    'enabled' => $item->isEnabled(),
                    'visible_for_roles' => $item->getVisibleForRoles(),
                    'visible_for_scopes' => $item->getVisibleForScopes(),
                    'visible_for_environments' => $item->getVisibleForEnvironments(),
                    'metadata' => $metadata,
                ];

                if (null !== $item->getRouteName()) {
                    $itemConfig['target'] = ['type' => 'route', 'route' => $item->getRouteName(), 'params' => $item->getRouteParameters()];
                } elseif (null !== $item->getPath()) {
                    $itemConfig['target'] = ['type' => 'path', 'path' => $item->getPath()];
                }

                if ($item->isArchived()) {
                    $archived[] = $menu->getMenuKey().':'.$item->getNavigationKey();
                }

                $items[$item->getNavigationKey()] = $itemConfig;
            }

            $groups[$menu->getMenuKey()] = [
                'label' => $menu->getLabel(),
                'slug' => $menu->getSlug(),
                'location' => $menu->getLocation(),
                'type' => $menu->getType(),
                'priority' => $menu->getPriority(),
                'enabled' => $menu->isEnabled(),
                'visible_for_roles' => $menu->getVisibleForRoles(),
                'visible_for_scopes' => $menu->getVisibleForScopes(),
                'visible_for_environments' => $menu->getVisibleForEnvironments(),
                'metadata' => $menu->getMetadata(),
                'items' => $items,
            ];
        }

        $payload = [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'shell_groups' => $groups,
            'archived_items' => $archived,
        ];

        $payload['sha256'] = $this->checksum($payload);
        return $payload;
    }

    /** @param array<string, mixed> $snapshot */
    public function restore(array $snapshot): int
    {
        $this->assertValid($snapshot);
        $count = $this->importService->replaceFromConfig(['shell_groups' => $snapshot['shell_groups']]);

        $archived = is_array($snapshot['archived_items'] ?? null) ? $snapshot['archived_items'] : [];
        if ([] !== $archived) {
            $lookup = array_fill_keys(array_filter($archived, 'is_string'), true);
            foreach ($this->menuRepository->findAll() as $menu) {
                foreach ($menu->getItems() as $item) {
                    if (isset($lookup[$menu->getMenuKey().':'.$item->getNavigationKey()])) {
                        $item->archive();
                    }
                }
            }
            $this->entityManager->flush();
        }

        return $count;
    }

    /** @param array<string, mixed> $snapshot */
    public function assertValid(array $snapshot): void
    {
        if (($snapshot['format'] ?? null) !== self::FORMAT || ($snapshot['version'] ?? null) !== self::VERSION) {
            throw new \InvalidArgumentException('Unsupported navigation snapshot format or version.');
        }
        if (!is_array($snapshot['shell_groups'] ?? null)) {
            throw new \InvalidArgumentException('Navigation snapshot has no shell_groups payload.');
        }
        $expected = $snapshot['sha256'] ?? null;
        if (!is_string($expected) || !hash_equals($expected, $this->checksum($snapshot))) {
            throw new \InvalidArgumentException('Navigation snapshot SHA-256 verification failed.');
        }
    }

    /** @param array<string, mixed> $payload */
    private function checksum(array $payload): string
    {
        unset($payload['sha256']);
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        return hash('sha256', $json);
    }
}
