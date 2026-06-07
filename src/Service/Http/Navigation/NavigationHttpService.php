<?php

declare(strict_types=1);

namespace App\Navigating\Service\Http\Navigation;

use App\Navigating\ServiceInterface\Navigation\Provide\NavigationResponseProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationTemplateDataProvideServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class NavigationHttpService
{
    public function __construct(
        private NavigationResponseProvideServiceInterface $responseProvider,
        private NavigationTemplateDataProvideServiceInterface $templateDataProvider,
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
