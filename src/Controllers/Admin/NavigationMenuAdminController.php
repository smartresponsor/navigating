<?php

declare(strict_types=1);

namespace App\Navigating\Controllers\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminRoute(path: '/navigation/menu', name: 'navigation_menu')]
#[IsGranted('ROLE_ADMIN')]
final class NavigationMenuAdminController extends AbstractController
{
    #[AdminRoute(path: '/', name: 'index')]
    public function index(): Response
    {
        return $this->renderNativePage(
            'Navigation menu',
            'Navigation menu CRUD entry point is registered through EasyAdmin. Storage and persistence stay owned by the host/admin layer.'
        );
    }

    #[AdminRoute(path: '/new', name: 'new')]
    public function new(): Response
    {
        return $this->renderNativePage(
            'New navigation menu item',
            'Native EasyAdmin page for the create form entry point. Form fields must be supplied by the host/admin persistence adapter.'
        );
    }

    #[AdminRoute(path: '/create', name: 'create')]
    public function create(): RedirectResponse
    {
        return $this->redirectToRoute('ea_navigation_menu_index');
    }

    #[AdminRoute(path: '/{id}', name: 'detail')]
    public function detail(string $id): Response
    {
        return $this->renderNativePage(
            'Navigation menu item',
            sprintf('Native EasyAdmin detail entry point for navigation menu item "%s".', $id)
        );
    }

    #[AdminRoute(path: '/{id}/edit', name: 'edit')]
    public function edit(string $id): Response
    {
        return $this->renderNativePage(
            'Edit navigation menu item',
            sprintf('Native EasyAdmin edit entry point for navigation menu item "%s".', $id)
        );
    }

    #[AdminRoute(path: '/{id}/update', name: 'update')]
    public function update(string $id): RedirectResponse
    {
        return $this->redirectToRoute('ea_navigation_menu_detail', ['id' => $id]);
    }

    #[AdminRoute(path: '/{id}/delete', name: 'delete')]
    public function delete(string $id): RedirectResponse
    {
        return $this->redirectToRoute('ea_navigation_menu_index');
    }

    #[AdminRoute(path: '/import', name: 'import')]
    public function import(): Response
    {
        return $this->renderNativePage(
            'Import navigation menu',
            'Native EasyAdmin import entry point. Import execution belongs to the host/admin layer.'
        );
    }

    #[AdminRoute(path: '/export', name: 'export')]
    public function export(): Response
    {
        return $this->renderNativePage(
            'Export navigation menu',
            'Native EasyAdmin export entry point. Export execution belongs to the host/admin layer.'
        );
    }

    private function renderNativePage(string $title, string $body): Response
    {
        return $this->render('@EasyAdmin/page/content.html.twig', [
            'page_title' => $title,
            'content_title' => $title,
            'main_content' => sprintf('<p>%s</p>', htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
        ]);
    }
}
