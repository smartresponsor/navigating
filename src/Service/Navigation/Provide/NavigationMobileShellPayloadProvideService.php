<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Provide;

use App\Navigating\Model\Navigation\View\NavigationItemView;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationMobileShellPayloadProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationShellProvideServiceInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationMobileShellPayloadProvideService implements NavigationMobileShellPayloadProvideServiceInterface
{
    public function __construct(
        private NavigationShellProvideServiceInterface $shellProvideService,
    ) {
    }

    public function provideMobileShell(Request $request): array
    {
        $mobileRequest = $request->duplicate();
        $mobileRequest->attributes->set('_navigation_environment', 'mobile');
        $mobileRequest->attributes->set('_navigation_scopes', ['mobile']);

        $locations = [];

        foreach ($this->shellProvideService->provideShell($mobileRequest)->groups as $location => $group) {
            if (!str_starts_with($location, 'shell.mobile.')) {
                continue;
            }

            $mobileLocation = substr($location, 6);
            $locations[$mobileLocation] ??= [];

            foreach ($group->items as $item) {
                $locations[$mobileLocation][] = $this->itemPayload($item, $mobileLocation, $group->label);
            }
        }

        return [
            'schema' => 'smartresponsor.navigation.mobile.shell.v1',
            'channel' => 'mobile',
            'platforms' => ['android', 'ios'],
            'locations' => $locations,
        ];
    }

    /** @return array<string, mixed> */
    private function itemPayload(NavigationItemView $item, string $location, string $groupLabel): array
    {
        return [
            'key' => $item->key,
            'label' => $item->label,
            'icon' => $item->icon,
            'badge' => $item->badge,
            'enabled' => !$item->disabled,
            'visible' => $item->visible,
            'status' => $item->disabled ? 'coming_soon' : 'active',
            'disabledReason' => $item->disabled ? 'item_disabled' : null,
            'requiredComponent' => $item->metadata['runtime_scope'] ?? null,
            'location' => $location,
            'group' => $item->metadata['group'] ?? null,
            'groupLabel' => $groupLabel,
            'action' => $item->target->action,
            'route' => is_string($item->metadata['mobile_route'] ?? null) ? $item->metadata['mobile_route'] : null,
            'target' => $item->target->toArray(),
            'metadata' => [
                'domain' => $item->metadata['domain'] ?? null,
                'resource' => $item->metadata['resource'] ?? null,
                'operation' => $item->metadata['operation'] ?? null,
                'platforms' => $item->metadata['mobile_platforms'] ?? ['android', 'ios'],
            ],
        ];
    }
}
