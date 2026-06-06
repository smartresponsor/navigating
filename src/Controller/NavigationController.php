<?php

declare(strict_types=1);

namespace App\Navigating\Controller;

use App\Navigating\Service\Navigation\NavigationResponseProvider;
use App\Navigating\Service\Navigation\NavigationTemplateDataProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class NavigationController
{
    public function __construct(
        private NavigationResponseProvider $responseProvider,
        private NavigationTemplateDataProvider $templateDataProvider,
    ) {
    }

    /**
     * @return Response|array<string, mixed>
     */
    public function index(Request $request): Response|array
    {
        return $this->responseProvider->providePayload($request);
    }

    public function preview(Request $request): Response
    {
        return new JsonResponse($this->templateDataProvider->provide($request));
    }
}
