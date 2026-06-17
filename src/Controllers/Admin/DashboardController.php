<?php

declare(strict_types=1);

namespace App\Navigating\Controllers\Admin;

use App\Navigating\Entity\NavigationItem;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/', routeName: 'ea')]
#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->redirectToRoute('ea_navigation_item_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Navigating')
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Navigation', 'fa fa-compass', NavigationItem::class);
    }
}
