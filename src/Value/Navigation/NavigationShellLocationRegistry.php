<?php

declare(strict_types=1);

namespace App\Navigating\Value\Navigation;

/**
 * Config-owned shell location registry.
 *
 * The component no longer hardcodes application shell locations in PHP.
 * A location becomes canonical when it is declared under navigation.shell_locations.
 */
final class NavigationShellLocationRegistry
{
    /**
     * @param array<string, mixed> $config
     *
     * @return list<string>
     */
    public static function all(array $config = []): array
    {
        $locations = self::configuredLocations($config);
        sort($locations);

        return $locations;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return list<string>
     */
    public static function configuredLocations(array $config): array
    {
        $configured = $config['shell_locations'] ?? [];

        if (!is_array($configured)) {
            return [];
        }

        $locations = [];

        foreach ($configured as $location => $_definition) {
            if (!is_string($location)) {
                continue;
            }

            $location = trim($location);

            if ('' === $location) {
                continue;
            }

            $locations[] = $location;
        }

        return array_values(array_unique($locations));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function isCanonical(string $location, array $config = []): bool
    {
        return in_array(trim($location), self::configuredLocations($config), true);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function canonicalListForMessage(array $config = []): string
    {
        $locations = self::all($config);

        if ([] === $locations) {
            return '(none configured; declare navigation.shell_locations)';
        }

        return implode(', ', $locations);
    }
}
