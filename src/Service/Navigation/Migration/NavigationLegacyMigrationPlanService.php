<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Migration;

use Doctrine\DBAL\Connection;

final readonly class NavigationLegacyMigrationPlanService
{
    /** @param array<string, mixed> $navigationConfig */
    public function __construct(
        private Connection $connection,
        private array $navigationConfig = [],
    ) {
    }

    /**
     * @return array{state:string, rows:int, shell_groups:array<string,mixed>, archived_items:list<string>, raw_rows:list<array<string,mixed>>}
     */
    public function createPlan(): array
    {
        $state = $this->detectState();
        if ('legacy' !== $state) {
            return ['state' => $state, 'rows' => 0, 'shell_groups' => [], 'archived_items' => [], 'raw_rows' => []];
        }

        $rows = $this->connection->executeQuery('SELECT * FROM navigation_item ORDER BY id ASC')->fetchAllAssociative();
        $converted = $this->convertRows($rows);

        return [
            'state' => 'legacy',
            'rows' => count($rows),
            'shell_groups' => $converted['shell_groups'],
            'archived_items' => $converted['archived_items'],
            'raw_rows' => $rows,
        ];
    }

    public function detectState(): string
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['navigation_item'])) {
            return $schemaManager->tablesExist(['navigation_menu']) ? 'unknown' : 'empty';
        }

        $columns = array_change_key_case($schemaManager->listTableColumns('navigation_item'), CASE_LOWER);
        if (isset($columns['menu_id'], $columns['parent_id'])) {
            return 'current';
        }
        if (isset($columns['parent_key'], $columns['location'], $columns['required_role'], $columns['created_at'], $columns['updated_at'])) {
            return 'legacy';
        }

        return 'unknown';
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{shell_groups:array<string,mixed>, archived_items:list<string>}
     */
    private function convertRows(array $rows): array
    {
        $canonicalGroups = is_array($this->navigationConfig['shell_groups'] ?? null) ? $this->navigationConfig['shell_groups'] : [];
        $keyToGroup = [];
        foreach ($canonicalGroups as $groupKey => $groupConfig) {
            if (!is_string($groupKey) || !is_array($groupConfig)) {
                continue;
            }
            foreach (($groupConfig['items'] ?? []) as $itemKey => $_itemConfig) {
                if (is_string($itemKey)) {
                    $keyToGroup[$itemKey] = $groupKey;
                }
            }
        }

        $rowByKey = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['navigation_key'] ?? ''));
            if ('' === $key) {
                throw new \RuntimeException('Legacy navigation contains a row with an empty navigation_key.');
            }
            if (isset($rowByKey[$key])) {
                throw new \RuntimeException(sprintf('Legacy navigation contains duplicate navigation_key "%s".', $key));
            }
            $rowByKey[$key] = $row;
        }

        $groupForKey = [];
        $legacyLocationForGroup = [];
        foreach ($rowByKey as $key => $row) {
            $location = $this->legacyLocation($row);
            $groupKey = $keyToGroup[$key] ?? 'legacy_'.$this->tokenize($location);
            $groupForKey[$key] = $groupKey;

            if (isset($legacyLocationForGroup[$groupKey]) && $legacyLocationForGroup[$groupKey] !== $location) {
                throw new \RuntimeException(sprintf(
                    'Legacy items mapped to future menu "%s" come from different locations (%s vs %s). Refusing lossy migration.',
                    $groupKey,
                    $legacyLocationForGroup[$groupKey],
                    $location,
                ));
            }
            $legacyLocationForGroup[$groupKey] = $location;

            $canonical = is_array($canonicalGroups[$groupKey] ?? null) ? $canonicalGroups[$groupKey] : [];
            $canonicalLocation = $this->nullableString($canonical['location'] ?? null);
            if (null !== $canonicalLocation && $canonicalLocation !== $location) {
                throw new \RuntimeException(sprintf(
                    'Legacy item "%s" is stored at "%s", but canonical menu "%s" targets "%s". Refusing location-changing migration.',
                    $key,
                    $location,
                    $groupKey,
                    $canonicalLocation,
                ));
            }
        }

        foreach ($rowByKey as $key => $row) {
            $parentKey = $this->nullableString($row['parent_key'] ?? null);
            if (null === $parentKey) {
                continue;
            }
            if (!isset($rowByKey[$parentKey])) {
                throw new \RuntimeException(sprintf('Legacy navigation item "%s" references missing parent "%s".', $key, $parentKey));
            }
            if ($groupForKey[$key] !== $groupForKey[$parentKey]) {
                throw new \RuntimeException(sprintf('Legacy parent relation "%s" -> "%s" crosses future menu boundaries (%s vs %s). Refusing lossy migration.', $key, $parentKey, $groupForKey[$key], $groupForKey[$parentKey]));
            }
        }

        $groups = [];
        $archived = [];
        foreach ($rowByKey as $key => $row) {
            $groupKey = $groupForKey[$key];
            $location = $legacyLocationForGroup[$groupKey];
            $canonical = is_array($canonicalGroups[$groupKey] ?? null) ? $canonicalGroups[$groupKey] : [];

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'label' => $canonical['label'] ?? $this->labelize($location),
                    'slug' => $canonical['slug'] ?? str_replace('_', '-', $groupKey),
                    'location' => $location,
                    'type' => $canonical['type'] ?? 'navigation',
                    'priority' => (int) ($canonical['priority'] ?? 100),
                    'enabled' => true,
                    'visible_for_roles' => [],
                    'visible_for_scopes' => [],
                    'visible_for_environments' => [],
                    'metadata' => array_merge(
                        is_array($canonical['metadata'] ?? null) ? $canonical['metadata'] : [],
                        ['legacy_migrated' => true, 'legacy_location' => $location],
                    ),
                    'items' => [],
                ];
            }

            $metadata = $this->decodeJsonObjectStrict($row['metadata'] ?? null, sprintf('metadata for legacy navigation item "%s"', $key));
            $parentKey = $this->nullableString($row['parent_key'] ?? null);
            if (null !== $parentKey) {
                $metadata['parent_key'] = $parentKey;
            }

            $requiredRole = $this->nullableString($row['required_role'] ?? null);
            $routeName = $this->nullableString($row['route_name'] ?? null);
            $operation = trim((string) ($row['operation'] ?? 'index')) ?: 'index';
            if (is_string($metadata['operation'] ?? null) && trim($metadata['operation']) !== '' && trim($metadata['operation']) !== $operation) {
                $metadata['legacy_metadata_operation'] = $metadata['operation'];
            }

            $item = [
                'type' => is_string($metadata['type'] ?? null) && '' !== trim($metadata['type']) ? trim($metadata['type']) : 'link',
                'label' => (string) ($row['label'] ?? $key),
                'slug' => $this->nullableString($row['slug'] ?? null),
                'operation' => $operation,
                'icon' => $this->nullableString($row['icon'] ?? null),
                'priority' => (int) ($row['position'] ?? 0),
                'enabled' => (bool) ($row['enabled'] ?? true),
                'visible_for_roles' => null === $requiredRole ? [] : [$requiredRole],
                'visible_for_scopes' => [],
                'visible_for_environments' => [],
                'metadata' => $metadata,
            ];
            if (null !== $routeName) {
                $item['target'] = [
                    'type' => 'route',
                    'route' => $routeName,
                    'params' => $this->decodeJsonObjectStrict($row['route_parameters'] ?? null, sprintf('route_parameters for legacy navigation item "%s"', $key)),
                ];
            } elseif ([] !== $this->decodeJsonObjectStrict($row['route_parameters'] ?? null, sprintf('route_parameters for legacy navigation item "%s"', $key))) {
                throw new \RuntimeException(sprintf('Legacy navigation item "%s" has route parameters but no route name.', $key));
            }

            $groups[$groupKey]['items'][$key] = $item;
            if (null !== $this->nullableString($row['archived_at'] ?? null)) {
                $archived[] = $groupKey.':'.$key;
            }
        }

        return ['shell_groups' => $groups, 'archived_items' => $archived];
    }

    /** @return array<string, mixed> */
    private function decodeJsonObjectStrict(mixed $value, string $field): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || '' === trim($value)) {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(sprintf('Invalid JSON in %s: %s', $field, $exception->getMessage()), 0, $exception);
        }

        if (!is_array($decoded) || ([] !== $decoded && array_is_list($decoded))) {
            throw new \RuntimeException(sprintf('%s must decode to a JSON object.', ucfirst($field)));
        }

        return $decoded;
    }

    /** @param array<string, mixed> $row */
    private function legacyLocation(array $row): string
    {
        $location = trim((string) ($row['location'] ?? ''));
        if ('' === $location) {
            throw new \RuntimeException('Legacy navigation contains a row with an empty location.');
        }

        $locations = $this->navigationConfig['shell_locations'] ?? [];
        if (!is_array($locations) || !array_key_exists($location, $locations)) {
            throw new \RuntimeException(sprintf('Legacy navigation location "%s" is not registered in shell_locations.', $location));
        }

        return $location;
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        return '' === $value ? null : $value;
    }

    private function tokenize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';
        return trim($value, '_') ?: 'navigation';
    }

    private function labelize(string $value): string
    {
        $value = preg_replace('/[._-]+/', ' ', trim($value)) ?? $value;
        return ucfirst($value);
    }
}
