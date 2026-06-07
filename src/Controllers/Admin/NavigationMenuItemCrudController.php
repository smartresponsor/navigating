<?php

declare(strict_types=1);

namespace App\Navigating\Controllers\Admin;

use App\Navigating\Entity\NavigationMenuItem;
use App\Navigating\Form\Type\Admin\JsonArrayTextareaType;
use App\Navigating\Form\Type\Admin\NavigationMenuItemLocationType;
use App\Navigating\Form\Type\Admin\NavigationMenuItemOperationType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class NavigationMenuItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NavigationMenuItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Navigation menu item')
            ->setEntityLabelInPlural('Navigation menu')
            ->setPageTitle(Crud::PAGE_INDEX, 'Navigation menu')
            ->setDefaultSort(['position' => 'ASC', 'id' => 'ASC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $bulk = Action::new('bulkItems', 'Bulk')->linkToCrudAction('bulkItems')->createAsGlobalAction();
        $import = Action::new('importItems', 'Import')->linkToCrudAction('importItems')->createAsGlobalAction();
        $export = Action::new('exportItems', 'Export')->linkToCrudAction('exportItems')->createAsGlobalAction();
        $archive = Action::new('archiveItem', 'Archive')->linkToCrudAction('archiveItem')->displayAsButton();
        $restore = Action::new('restoreItem', 'Restore')->linkToCrudAction('restoreItem')->displayAsButton();
        $duplicate = Action::new('duplicateItem', 'Duplicate')->linkToCrudAction('duplicateItem')->displayAsButton();

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $bulk)
            ->add(Crud::PAGE_INDEX, $import)
            ->add(Crud::PAGE_INDEX, $export)
            ->add(Crud::PAGE_INDEX, $archive)
            ->add(Crud::PAGE_INDEX, $restore)
            ->add(Crud::PAGE_INDEX, $duplicate)
            ->add(Crud::PAGE_DETAIL, $archive)
            ->add(Crud::PAGE_DETAIL, $restore)
            ->add(Crud::PAGE_DETAIL, $duplicate)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('menuKey')->setHelp('Stable business key, for example navigation.menu.index.');
        yield TextField::new('parentKey')->setRequired(false)->hideOnIndex();
        yield TextField::new('label');
        yield TextField::new('slug')->setRequired(false);
        yield TextField::new('routeName');
        yield TextareaField::new('routeParameters')
            ->setFormType(JsonArrayTextareaType::class)
            ->setHelp('JSON object passed to the resolved backend route.')
            ->hideOnIndex()
        ;
        yield TextField::new('location')
            ->setFormType(NavigationMenuItemLocationType::class)
        ;
        yield TextField::new('operation')
            ->setFormType(NavigationMenuItemOperationType::class)
        ;
        yield TextField::new('icon')->setRequired(false)->hideOnIndex();
        yield TextField::new('requiredRole')->setRequired(false)->hideOnIndex();
        yield IntegerField::new('position');
        yield BooleanField::new('enabled');
        yield TextareaField::new('metadata')
            ->setFormType(JsonArrayTextareaType::class)
            ->setHelp('JSON object for UI/runtime flags that do not belong to route parameters.')
            ->hideOnIndex()
        ;
        yield DateTimeField::new('archivedAt')->hideOnForm();
        yield DateTimeField::new('createdAt')->hideOnForm();
        yield DateTimeField::new('updatedAt')->hideOnForm();
    }

    public function bulkItems(AdminContext $context): Response
    {
        return $this->render('@EasyAdmin/page/content.html.twig', [
            'page_title' => 'Bulk navigation menu',
            'content_title' => 'Bulk navigation menu',
            'main_content' => '<p>Native EasyAdmin CRUD action entry point. Bulk execution is intentionally left to the admin workflow.</p>',
        ]);
    }

    public function importItems(AdminContext $context): Response
    {
        return $this->render('@EasyAdmin/page/content.html.twig', [
            'page_title' => 'Import navigation menu',
            'content_title' => 'Import navigation menu',
            'main_content' => '<p>Native EasyAdmin CRUD action entry point. Import execution is intentionally left to the admin workflow.</p>',
        ]);
    }

    public function exportItems(AdminContext $context): Response
    {
        return $this->render('@EasyAdmin/page/content.html.twig', [
            'page_title' => 'Export navigation menu',
            'content_title' => 'Export navigation menu',
            'main_content' => '<p>Native EasyAdmin CRUD action entry point. Export execution is intentionally left to the admin workflow.</p>',
        ]);
    }

    public function archiveItem(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $item = $this->resolveMenuItem($context);
        $item->archive();
        $entityManager->flush();

        return $this->redirectToRoute('navigation.menu.index');
    }

    public function restoreItem(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $item = $this->resolveMenuItem($context);
        $item->restore();
        $entityManager->flush();

        return $this->redirectToRoute('navigation.menu.index');
    }

    public function duplicateItem(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $item = $this->resolveMenuItem($context);
        $copy = (new NavigationMenuItem())
            ->setMenuKey($item->getMenuKey().'.copy.'.date('YmdHis'))
            ->setParentKey($item->getParentKey())
            ->setLabel($item->getLabel().' copy')
            ->setSlug(null === $item->getSlug() ? null : $item->getSlug().'-copy-'.date('YmdHis'))
            ->setRouteName($item->getRouteName())
            ->setRouteParameters($item->getRouteParameters())
            ->setLocation($item->getLocation())
            ->setOperation($item->getOperation())
            ->setIcon($item->getIcon())
            ->setRequiredRole($item->getRequiredRole())
            ->setPosition($item->getPosition() + 1)
            ->setEnabled($item->isEnabled())
            ->setMetadata($item->getMetadata())
        ;

        $entityManager->persist($copy);
        $entityManager->flush();

        return $this->redirectToRoute('navigation.menu.index');
    }

    private function resolveMenuItem(AdminContext $context): NavigationMenuItem
    {
        $entity = $context->getEntity();
        $instance = null === $entity ? null : $entity->getInstance();

        if (!$instance instanceof NavigationMenuItem) {
            throw $this->createNotFoundException('Navigation menu item was not resolved for the EasyAdmin action.');
        }

        return $instance;
    }
}
