<?php

declare(strict_types=1);

namespace App\Navigating\Controllers\Admin;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Form\Type\Admin\JsonArrayTextareaType;
use App\Navigating\Form\Type\Admin\JsonListTextareaType;
use App\Navigating\Form\Type\Admin\NavigationItemOperationType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
            ->setEntityLabelInPlural('Navigation items')
            ->setPageTitle(Crud::PAGE_INDEX, 'Navigation items')
            ->setDefaultSort(['position' => 'ASC', 'id' => 'ASC'])
            ->setSearchFields([
                'navigationKey',
                'slug',
                'label',
                'type',
                'operation',
                'routeName',
                'path',
                'menu.menuKey',
                'menu.label',
                'parent.navigationKey',
                'parent.label',
            ])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $archive = Action::new('archiveItem', 'Archive')->linkToCrudAction('archiveItem')->displayAsButton();
        $restore = Action::new('restoreItem', 'Restore')->linkToCrudAction('restoreItem')->displayAsButton();
        $duplicate = Action::new('duplicateItem', 'Duplicate')->linkToCrudAction('duplicateItem')->displayAsButton();

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
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
        yield AssociationField::new('menu')
            ->setRequired(true)
            ->autocomplete()
            ->setHelp('The item belongs to exactly one navigation menu.')
        ;
        yield AssociationField::new('parent')
            ->setRequired(false)
            ->autocomplete()
            ->setHelp('Parent must belong to the same menu. The entity invariant rejects cross-menu hierarchy.')
            ->hideOnIndex()
        ;
        yield TextField::new('navigationKey')->setHelp('Stable business key, for example catalog.index.');
        yield TextField::new('label');
        yield TextField::new('slug')->setRequired(false);
        yield TextField::new('type');
        yield TextField::new('routeName')->setRequired(false);
        yield TextField::new('path')->setRequired(false)->hideOnIndex();
        yield TextareaField::new('routeParameters')
            ->setFormType(JsonArrayTextareaType::class)
            ->setHelp('JSON object passed to the resolved backend route.')
            ->hideOnIndex()
        ;
        yield TextField::new('operation')->setFormType(NavigationItemOperationType::class);
        yield TextField::new('icon')->setRequired(false)->hideOnIndex();
        yield TextField::new('badge')->setRequired(false)->hideOnIndex();
        yield TextareaField::new('visibleForRoles')
            ->setFormType(JsonListTextareaType::class)
            ->setHelp('JSON array of required roles.')
            ->hideOnIndex()
        ;
        yield TextareaField::new('visibleForScopes')
            ->setFormType(JsonListTextareaType::class)
            ->setHelp('JSON array of navigation scopes.')
            ->hideOnIndex()
        ;
        yield TextareaField::new('visibleForEnvironments')
            ->setFormType(JsonListTextareaType::class)
            ->setHelp('JSON array of environments.')
            ->hideOnIndex()
        ;
        yield IntegerField::new('position');
        yield BooleanField::new('enabled');
        yield TextareaField::new('metadata')
            ->setFormType(JsonArrayTextareaType::class)
            ->setHelp('JSON object for UI/runtime metadata. Hierarchy is represented by the parent association, not metadata.parent_key.')
            ->hideOnIndex()
        ;
        yield DateTimeField::new('archivedAt')->hideOnForm();
        yield DateTimeField::new('objectCreatedAt')->hideOnForm();
        yield DateTimeField::new('objectModifiedAt')->hideOnForm();
    }

    public function archiveItem(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $item = $this->resolveNavigationItem($context);
        $item->archive();
        $entityManager->flush();

        return $this->redirectToRoute('ea_navigation_item_index');
    }

    public function restoreItem(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $item = $this->resolveNavigationItem($context);
        $item->restore();
        $entityManager->flush();

        return $this->redirectToRoute('ea_navigation_item_index');
    }

    public function duplicateItem(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $item = $this->resolveNavigationItem($context);
        $timestamp = date('YmdHis');
        $copy = (new NavigationItem())
            ->setMenu($item->getMenu())
            ->setParent($item->getParent())
            ->setNavigationKey($this->appendWithinLimit($item->getNavigationKey(), '.copy.'.$timestamp, 160))
            ->setLabel($this->appendWithinLimit($item->getLabel(), ' copy', 140))
            ->setSlug(null === $item->getSlug() ? null : $this->appendWithinLimit($item->getSlug(), '-copy-'.$timestamp, 180))
            ->setType($item->getType())
            ->setRouteName($item->getRouteName())
            ->setPath($item->getPath())
            ->setRouteParameters($item->getRouteParameters())
            ->setOperation($item->getOperation())
            ->setIcon($item->getIcon())
            ->setBadge($item->getBadge())
            ->setVisibleForRoles($item->getVisibleForRoles())
            ->setVisibleForScopes($item->getVisibleForScopes())
            ->setVisibleForEnvironments($item->getVisibleForEnvironments())
            ->setPosition($item->getPosition() + 1)
            ->setEnabled($item->isEnabled())
            ->setMetadata($item->getMetadata())
        ;

        $entityManager->persist($copy);
        $entityManager->flush();

        return $this->redirectToRoute('ea_navigation_item_index');
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

    private function appendWithinLimit(string $base, string $suffix, int $maxLength): string
    {
        $allowed = max(0, $maxLength - $this->characterLength($suffix));

        return $this->substring($base, $allowed).$suffix;
    }

    private function substring(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length, 'UTF-8');
        }

        if (1 === preg_match_all('/./us', $value, $matches)) {
            return implode('', array_slice($matches[0], 0, $length));
        }

        return substr($value, 0, $length);
    }

    private function characterLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        $matched = preg_match_all('/./us', $value, $matches);

        return false === $matched ? strlen($value) : $matched;
    }
}
