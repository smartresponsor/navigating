<?php

declare(strict_types=1);

namespace App\Navigating\Controllers\Admin;

use App\Navigating\Repository\NavigationItemRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/navigation')]
#[IsGranted('ROLE_ADMIN')]
final class NavigationCrudRouteController extends AbstractController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly NavigationItemRepository $navigationItemRepository,
    ) {
    }

    #[Route('/index', name: 'navigation.index', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return $this->redirectToCrud(Action::INDEX);
    }

    #[Route('/show/{id}', name: 'navigation.show_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showById(int $id): RedirectResponse
    {
        return $this->redirectToCrud(Action::DETAIL, $id);
    }

    #[Route('/show/{slug}', name: 'navigation.show_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['GET'])]
    public function showBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrud(Action::DETAIL, $this->resolveSlugId($slug));
    }

    #[Route('/new', name: 'navigation.new', methods: ['GET'])]
    public function new(): RedirectResponse
    {
        return $this->redirectToCrud(Action::NEW);
    }

    #[Route('/create', name: 'navigation.create', methods: ['POST'])]
    public function create(): RedirectResponse
    {
        return $this->redirectToCrud(Action::NEW);
    }

    #[Route('/edit/{id}', name: 'navigation.edit_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function editById(int $id): RedirectResponse
    {
        return $this->redirectToCrud(Action::EDIT, $id);
    }

    #[Route('/edit/{slug}', name: 'navigation.edit_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['GET'])]
    public function editBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrud(Action::EDIT, $this->resolveSlugId($slug));
    }

    #[Route('/update/{id}', name: 'navigation.update_id', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateById(int $id): RedirectResponse
    {
        return $this->redirectToCrud(Action::EDIT, $id);
    }

    #[Route('/update/{slug}', name: 'navigation.update_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['POST'])]
    public function updateBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrud(Action::EDIT, $this->resolveSlugId($slug));
    }

    #[Route('/delete/{id}', name: 'navigation.delete_id', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteById(int $id): RedirectResponse
    {
        return $this->redirectToCrud(Action::DETAIL, $id);
    }

    #[Route('/delete/{slug}', name: 'navigation.delete_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['POST'])]
    public function deleteBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrud(Action::DETAIL, $this->resolveSlugId($slug));
    }

    #[Route('/bulk', name: 'navigation.bulk', methods: ['POST'])]
    public function bulk(): Response
    {
        return $this->redirectToCrudAction('bulkItems');
    }

    #[Route('/import', name: 'navigation.import', methods: ['GET', 'POST'])]
    public function import(): Response
    {
        return $this->redirectToCrudAction('importItems');
    }

    #[Route('/export', name: 'navigation.export', methods: ['GET'])]
    public function export(): Response
    {
        return $this->redirectToCrudAction('exportItems');
    }

    #[Route('/archive/{id}', name: 'navigation.archive_id', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function archiveById(int $id): RedirectResponse
    {
        return $this->redirectToCrudAction('archiveItem', $id);
    }

    #[Route('/archive/{slug}', name: 'navigation.archive_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['POST'])]
    public function archiveBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrudAction('archiveItem', $this->resolveSlugId($slug));
    }

    #[Route('/restore/{id}', name: 'navigation.restore_id', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function restoreById(int $id): RedirectResponse
    {
        return $this->redirectToCrudAction('restoreItem', $id);
    }

    #[Route('/restore/{slug}', name: 'navigation.restore_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['POST'])]
    public function restoreBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrudAction('restoreItem', $this->resolveSlugId($slug));
    }

    #[Route('/duplicate/{id}', name: 'navigation.duplicate_id', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function duplicateById(int $id): RedirectResponse
    {
        return $this->redirectToCrudAction('duplicateItem', $id);
    }

    #[Route('/duplicate/{slug}', name: 'navigation.duplicate_slug', requirements: ['slug' => '[A-Za-z0-9][A-Za-z0-9_-]*'], methods: ['POST'])]
    public function duplicateBySlug(string $slug): RedirectResponse
    {
        return $this->redirectToCrudAction('duplicateItem', $this->resolveSlugId($slug));
    }

    private function redirectToCrud(string $action, ?int $entityId = null): RedirectResponse
    {
        $generator = $this->adminUrlGenerator
            ->unsetAll()
            ->setDashboard(DashboardController::class)
            ->setController(NavigationItemCrudController::class)
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
        $item = $this->navigationItemRepository->findOneBySlug($slug);
        if (null === $item || null === $item->getId()) {
            throw $this->createNotFoundException(sprintf('Navigation item with slug "%s" was not found.', $slug));
        }

        return $item->getId();
    }
}
