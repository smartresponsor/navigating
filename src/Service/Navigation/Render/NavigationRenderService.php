<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Render;

use App\Navigating\Model\Navigation\View\NavigationItemView;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationGroupProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Render\NavigationRenderServiceInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationRenderService implements NavigationRenderServiceInterface
{
    public function __construct(
        private NavigationGroupProvideServiceInterface $groupProvideService,
    ) {
    }

    public function renderGroup(string $location, Request $request): string
    {
        $group = $this->groupProvideService->provideGroup($location, $request);
        $attributes = sprintf(
            'data-navigation-location="%s" data-navigation-type="%s"',
            $this->escape($group->location),
            $this->escape($group->type),
        );
        $html = sprintf(
            '<nav class="interfacing-navigation-provider interfacing-provider-navigation-menu interfacing-provider-navigation-menu--native" %s><ul class="interfacing-menu-list">',
            $attributes,
        );

        foreach ($group->items as $item) {
            if (!$item->visible) {
                continue;
            }

            $html .= $this->renderItem($item);
        }

        return $html.'</ul></nav>';
    }

    private function renderItem(NavigationItemView $item): string
    {
        $class = $item->active ? ' class="interfacing-list-item is-active"' : ' class="interfacing-list-item"';
        $linkClass = $item->active
            ? ' class="interfacing-nav-link is-active"'
            : ' class="interfacing-nav-link"';
        $href = '' !== $item->target->href ? $item->target->href : '#';
        $icon = null === $item->icon ? '' : sprintf('<span data-navigation-icon="%s"></span>', $this->escape($item->icon));
        $badge = null === $item->badge ? '' : sprintf('<span data-navigation-badge>%s</span>', $this->escape($item->badge));

        return sprintf(
            '<li data-navigation-key="%s"%s><a%s href="%s">%s<span class="interfacing-nav-link__label">%s</span>%s</a></li>',
            $this->escape($item->key),
            $class,
            $linkClass,
            $this->escape($href),
            $icon,
            $this->escape($item->label),
            $badge,
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
