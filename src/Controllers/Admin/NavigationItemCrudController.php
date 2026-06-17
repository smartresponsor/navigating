<?php

declare(strict_types=1);

namespace App\Navigating\Controllers\Admin;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Form\Type\Admin\JsonArrayTextareaType;
use App\Navigating\Form\Type\Admin\NavigationItemLocationType;
use App\Navigating\Form\Type\Admin\NavigationItemOperationType;
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
final class NavigationItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NavigationItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Navigation item')
            ->setEntityLabelInPlural('Navigation')
            ->setPageTitle(Crud::PAGE_INDEX, 'Navigation')
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
        yield TextField::new('navigationKey')->setHelp('Stable business key, for example navigation.index.');
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
            ->setFormType(NavigationItemLocationType::class)
        ;
        yield TextField::new('operation')
            ->setFormType(NavigationItemOperationType::class)
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
            'page_title' => 'Bulk navigation',
            'content_title' => 'Bulk navigation',
            'main_content' => '<p>Native EasyAdmin CRUD action entry point. Bulk execution is intentionally left to the admin workflow.</p>',
        ]);
    }

    public function importItems(AdminContext $context): Response
    {
        return $this->render('@EasyAdmin/page/content.html.twig', [
            'page_title' => 'Import navigation',
            'content_title' => 'Import navigation',
            'main_content' => '<p>Native EasyAdmin CRUD action entry point. Import execution is intentionally left to the admin workflow.</p>',
        ]);
    }

    public function exportItems(AdminContext $context): Response
    {
        return $this->render('@EasyAdmin/page/content.html.twig', [
            'page_title' => 'Export navigation',
            'content_title' => 'Export navigation',
            'main_content' => '<p>Native EasyAdmin CRUD action entry point. Export execution is intentionally left to the admin workflow.</p>',
        ]);
    }

    public function archiveItem(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $item = $this->resolveNavigationItem($context);
        $item->archive();
        $entityManager->flush();

        return $this->redirectToRoute('navigation.index');
    }

    public function restoreItem(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $item = $this->resolveNavigationItem($context);
        $item->restore();
        $entityManager->flush();

        return $this->redirectToRoute('navigation.index');
    }

    public function duplicateItem(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $item = $this->resolveNavigationItem($context);
        $copy = (new NavigationItem())
            ->setNavigationKey($item->getNavigationKey().'.copy.'.date('YmdHis'))
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

        return $this->redirectToRoute('navigation.index');
    }

    private function resolveNavigationItem(AdminContext $context): NavigationItem
    {
        $entity = $context->getEntity();
        $instance = null === $entity ? null : $entity->getInstance();

        if (!$instance instanceof NavigationItem) {
            throw $this->createNotFoundException('Navigation item was not resolved for the EasyAdmin action.');
        }

        return $instance;
    }
}
