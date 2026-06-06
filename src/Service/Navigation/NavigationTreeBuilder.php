<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation;

use App\Navigating\Value\Navigation\NavigationItemView;
use App\Navigating\Value\Navigation\NavigationShellGroup;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationTreeBuilder
{
    public function __construct(
        private NavigationTargetResolver $targetResolver,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function buildShellGroupItems(NavigationShellGroup $group, Request $request): array
    {
        $items = [];

        foreach ($group->items as $item) {
            if (!$item->enabled) {
                continue;
            }

            $url = null === $item->target ? '' : $this->targetResolver->resolveUrl($item->target);
            $metadata = array_replace($item->metadata, [
                'level' => 'shell_item',
                'group' => $group->key,
                'group_label' => $group->label,
                'group_type' => $group->type,
                'location' => $group->location,
                'item_type' => $item->type,
                'target_type' => null === $item->target ? null : $item->target->type,
            ]);

            if (null !== $item->icon) {
                $metadata['icon'] = $item->icon;
            }

            if (null !== $item->badge) {
                $metadata['badge'] = $item->badge;
            }

            if (null !== $item->action) {
                $metadata['action'] = $item->action;
            }

            if (null !== $item->widget) {
                $metadata['widget'] = $item->widget;
            }

            $items[] = (new NavigationItemView(
                key: $item->key,
                type: $item->type,
                label: $item->label,
                url: $url,
                active: '' !== $url && $this->pathMatchesRequest($request, $url),
                priority: $item->priority,
                action: $item->action,
                widget: $item->widget,
                icon: $item->icon,
                badge: $item->badge,
                metadata: $metadata,
            ))->toArray();
        }

        return $items;
    }

    private function pathMatchesRequest(Request $request, string $candidateUrl): bool
    {
        $candidatePath = parse_url($candidateUrl, PHP_URL_PATH);

        if (!is_string($candidatePath) || '' === $candidatePath) {
            return false;
        }

        $currentPath = '/' === $request->getPathInfo() ? '/' : rtrim($request->getPathInfo(), '/');
        $candidatePath = '/' === $candidatePath ? '/' : rtrim($candidatePath, '/');

        if ('/' === $candidatePath) {
            return '/' === $currentPath;
        }

        return $currentPath === $candidatePath || str_starts_with($currentPath, $candidatePath.'/');
    }
}
