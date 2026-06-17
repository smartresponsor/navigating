<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Build;

use App\Navigating\Model\Navigation\View\NavigationGroupView;
use App\Navigating\Model\Navigation\View\NavigationItemView;
use App\Navigating\Model\Navigation\View\NavigationTargetView;
use App\Navigating\ServiceInterface\Navigation\Build\NavigationTreeBuildServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Filter\NavigationRuntimeTargetFilterServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Resolve\NavigationTargetResolveServiceInterface;
use App\Navigating\Value\Navigation\NavigationShellGroup;
use App\Navigating\Value\Navigation\NavigationShellItem;
use App\Navigating\Value\Navigation\NavigationShellItemTypeRegistry;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationTreeBuildService implements NavigationTreeBuildServiceInterface
{
    public function __construct(
        private NavigationTargetResolveServiceInterface $targetResolveService,
        private NavigationRuntimeTargetFilterServiceInterface $runtimeTargetFilterService,
    ) {
    }

    public function buildGroup(NavigationShellGroup $group, Request $request): NavigationGroupView
    {
        $items = [];

        foreach ($group->items as $item) {
            if (!$item->enabled || !$item->visible) {
                continue;
            }

            $href = null === $item->target ? '' : $this->targetResolveService->resolveUrl($item->target);

            if (
                NavigationShellItemTypeRegistry::LINK === $item->type
                && !$this->runtimeTargetFilterService->allows($href, $request)
            ) {
                continue;
            }

            $items[] = $this->buildItem($group, $item, $request, $href);
        }

        return new NavigationGroupView(
            location: $group->location,
            label: $group->label,
            items: $items,
            type: $group->type,
            metadata: [
                'group' => $group->key,
                'item_count' => count($items),
            ],
        );
    }

    private function buildItem(
        NavigationShellGroup $group,
        NavigationShellItem $item,
        Request $request,
        string $href,
    ): NavigationItemView {
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

        return new NavigationItemView(
            key: $item->key,
            type: $item->type,
            label: $item->label,
            target: new NavigationTargetView(
                type: null === $item->target ? 'none' : $item->target->type,
                href: $href,
                route: $item->target?->route,
                path: $item->target?->path,
                action: $item->action,
                widget: $item->widget,
                parameters: $item->target?->params ?? [],
                query: $item->target?->query ?? [],
            ),
            active: '' !== $href && $this->pathMatchesRequest($request, $href),
            priority: $item->priority,
            icon: $item->icon,
            badge: $item->badge,
            metadata: $metadata,
        );
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
