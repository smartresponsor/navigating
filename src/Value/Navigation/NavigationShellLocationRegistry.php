<?php

declare(strict_types=1);

namespace App\Navigating\Value\Navigation;

final class NavigationShellLocationRegistry
{
    public const BODY_TOP = 'shell.body.top';

    public const LEFT_TOP = 'shell.left.top';
    public const LEFT_MIDDLE = 'shell.left.middle';
    public const LEFT_BOTTOM = 'shell.left.bottom';

    public const CONTEXT_TOP = 'shell.context.top';
    public const CONTEXT_MIDDLE = 'shell.context.middle';
    public const CONTEXT_BOTTOM = 'shell.context.bottom';

    public const MAIN_TOP = 'shell.main.top';
    public const MAIN_TOOLBAR = 'shell.main.toolbar';
    public const MAIN_CONTENT = 'shell.main.content';
    public const MAIN_BOTTOM = 'shell.main.bottom';

    public const RIGHT_TOP = 'shell.right.top';
    public const RIGHT_TOOL = 'shell.right.tool';
    public const RIGHT_FILTER = 'shell.right.filter';
    public const RIGHT_MIDDLE = 'shell.right.middle';
    public const RIGHT_BOTTOM = 'shell.right.bottom';

    public const FOOTER_TOP = 'shell.footer.top';
    public const FOOTER_LEFT = 'shell.footer.left';
    public const FOOTER_CONTEXT = 'shell.footer.context';
    public const FOOTER_MAIN = 'shell.footer.main';
    public const FOOTER_RIGHT = 'shell.footer.right';

    public const HEADER_BOTTOM = 'shell.header.bottom';

    /**
     * @var list<string>
     */
    public const ALL = [
        self::BODY_TOP,
        self::LEFT_TOP,
        self::LEFT_MIDDLE,
        self::LEFT_BOTTOM,
        self::CONTEXT_TOP,
        self::CONTEXT_MIDDLE,
        self::CONTEXT_BOTTOM,
        self::MAIN_TOP,
        self::MAIN_TOOLBAR,
        self::MAIN_CONTENT,
        self::MAIN_BOTTOM,
        self::RIGHT_TOP,
        self::RIGHT_TOOL,
        self::RIGHT_FILTER,
        self::RIGHT_MIDDLE,
        self::RIGHT_BOTTOM,
        self::FOOTER_TOP,
        self::FOOTER_LEFT,
        self::FOOTER_CONTEXT,
        self::FOOTER_MAIN,
        self::FOOTER_RIGHT,
        self::HEADER_BOTTOM,
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::ALL;
    }

    public static function isCanonical(string $location): bool
    {
        return in_array($location, self::ALL, true);
    }

    public static function canonicalListForMessage(): string
    {
        return implode(', ', self::ALL);
    }
}
