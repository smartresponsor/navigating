<?php

declare(strict_types=1);

namespace App\Navigating\Controllers\Admin;

use App\Navigating\Repository\NavigationMenuItemRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/navigation/menu')]
#[IsGranted('ROLE_ADMIN')]
final class NavigationMenuCrudRouteController extends AbstractController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly NavigationMenuItemRepository $navigationMenuItemRepository,
    ) {
    }

    #[Route('/index', name: 'navigation.menu.index', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return $this->redirectToCrud(Action::INDEX);
    }

    #[Route('/show/{id}', name: 'navigation.menu.show_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showById(int $id): RedirectResponse
    {
        return $this->redirectToCrud(Action::DETAIL, $id);
    }

    #[Route('/show/{slug}', name: 'navigation.menu.show_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['GET'])]
    public function showBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrud(Action::DETAIL, $this->resolveSlugId($slug));
    }

    #[Route('/new', name: 'navigation.menu.new', methods: ['GET'])]
    public function new(): RedirectResponse
    {
        return $this->redirectToCrud(Action::NEW);
    }

    #[Route('/create', name: 'navigation.menu.create', methods: ['POST'])]
    public function create(): RedirectResponse
    {
        return $this->redirectToCrud(Action::NEW);
    }

    #[Route('/edit/{id}', name: 'navigation.menu.edit_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function editById(int $id): RedirectResponse
    {
        return $this->redirectToCrud(Action::EDIT, $id);
    }

    #[Route('/edit/{slug}', name: 'navigation.menu.edit_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['GET'])]
    public function editBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrud(Action::EDIT, $this->resolveSlugId($slug));
    }

    #[Route('/update/{id}', name: 'navigation.menu.update_id', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateById(int $id): RedirectResponse
    {
        return $this->redirectToCrud(Action::EDIT, $id);
    }

    #[Route('/update/{slug}', name: 'navigation.menu.update_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['POST'])]
    public function updateBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrud(Action::EDIT, $this->resolveSlugId($slug));
    }

    #[Route('/delete/{id}', name: 'navigation.menu.delete_id', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteById(int $id): RedirectResponse
    {
        return $this->redirectToCrud(Action::DETAIL, $id);
    }

    #[Route('/delete/{slug}', name: 'navigation.menu.delete_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['POST'])]
    public function deleteBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrud(Action::DETAIL, $this->resolveSlugId($slug));
    }

    #[Route('/bulk', name: 'navigation.menu.bulk', methods: ['POST'])]
    public function bulk(): Response
    {
        return $this->redirectToCrudAction('bulkItems');
    }

    #[Route('/import', name: 'navigation.menu.import', methods: ['GET', 'POST'])]
    public function import(): Response
    {
        return $this->redirectToCrudAction('importItems');
    }

    #[Route('/export', name: 'navigation.menu.export', methods: ['GET'])]
    public function export(): Response
    {
        return $this->redirectToCrudAction('exportItems');
    }

    #[Route('/archive/{id}', name: 'navigation.menu.archive_id', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function archiveById(int $id): RedirectResponse
    {
        return $this->redirectToCrudAction('archiveItem', $id);
    }

    #[Route('/archive/{slug}', name: 'navigation.menu.archive_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['POST'])]
    public function archiveBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrudAction('archiveItem', $this->resolveSlugId($slug));
    }

    #[Route('/restore/{id}', name: 'navigation.menu.restore_id', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function restoreById(int $id): RedirectResponse
    {
        return $this->redirectToCrudAction('restoreItem', $id);
    }

    #[Route('/restore/{slug}', name: 'navigation.menu.restore_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['POST'])]
    public function restoreBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrudAction('restoreItem', $this->resolveSlugId($slug));
    }

    #[Route('/duplicate/{id}', name: 'navigation.menu.duplicate_id', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function duplicateById(int $id): RedirectResponse
    {
        return $this->redirectToCrudAction('duplicateItem', $id);
    }

    #[Route('/duplicate/{slug}', name: 'navigation.menu.duplicate_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['POST'])]
    public function duplicateBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrudAction('duplicateItem', $this->resolveSlugId($slug));
    }

    private function redirectToCrud(string $action, ?int $entityId = null): RedirectResponse
    {
        $generator = $this->adminUrlGenerator
            ->unsetAll()
            ->setDashboard(DashboardController::class)
            ->setController(NavigationMenuItemCrudController::class)
            ->setAction($action)
        ;

        if (null !== $entityId) {
            $generator->setEntityId($entityId);
        }

        return $this->redirect($generator->generateUrl());
    }

    private function redirectToCrudAction(string $crudAction, ?int $entityId = null): RedirectResponse
    {
        return $this->redirectToCrud($crudAction, $entityId);
    }

    private function resolveSlugId(string $slug): int
    {
        $item = $this->navigationMenuItemRepository->findOneBySlug($slug);
        if (null === $item || null === $item->getId()) {
            throw $this->createNotFoundException(sprintf('Navigation menu item with slug "%s" was not found.', $slug));
        }

        return $item->getId();
    }
}
