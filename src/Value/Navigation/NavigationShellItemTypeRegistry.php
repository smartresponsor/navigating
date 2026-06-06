<?php

declare(strict_types=1);

namespace App\Navigating\Value\Navigation;

final class NavigationShellItemTypeRegistry
{
    public const LINK = 'link';
    public const ACTION = 'action';
    public const HEADING = 'heading';
    public const SEPARATOR = 'separator';
    public const WIDGET = 'widget';
    public const BADGE = 'badge';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::LINK,
            self::ACTION,
            self::HEADING,
            self::SEPARATOR,
            self::WIDGET,
            self::BADGE,
        ];
    }

    public static function isCanonical(string $type): bool
    {
        return in_array($type, self::all(), true);
    }

    public static function canonicalListForMessage(): string
    {
        return implode(', ', self::all());
    }

    /**
     * @return list<string>
     */
    public static function targetlessTypes(): array
    {
        return [
            self::ACTION,
            self::HEADING,
            self::SEPARATOR,
            self::WIDGET,
            self::BADGE,
        ];
    }
}
