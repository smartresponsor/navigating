<?php

declare(strict_types=1);

namespace App\Navigating\Controllers\Admin;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Form\Type\Admin\JsonArrayTextareaType;
use App\Navigating\Form\Type\Admin\JsonListTextareaType;
use App\Navigating\Form\Type\Admin\NavigationItemOperationType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class NavigationItemCrudController extends AbstractCrudController
{
    private const EXPECTED_VERSION_FIELD = '_navigation_expected_version';
    private const STATE_ACTION_TEMPLATE = '@Navigating/admin/action/navigation_item_state_change.html.twig';

    public function __construct(
        private readonly AdminContextProvider $adminContextProvider,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

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
        $archive = Action::new('archiveItem', 'Archive')
            ->linkToCrudAction('archiveItem')
            ->renderAsForm()
            ->setTemplatePath(self::STATE_ACTION_TEMPLATE)
            ->askConfirmation('Archive this navigation item?')
        ;
        $restore = Action::new('restoreItem', 'Restore')
            ->linkToCrudAction('restoreItem')
            ->renderAsForm()
            ->setTemplatePath(self::STATE_ACTION_TEMPLATE)
        ;
        $duplicate = Action::new('duplicateItem', 'Duplicate')
            ->linkToCrudAction('duplicateItem')
            ->renderAsForm()
            ->setTemplatePath(self::STATE_ACTION_TEMPLATE)
        ;

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
        $currentInstance = $this->adminContextProvider->getContext()?->getEntity()?->getInstance();
        $currentItem = $currentInstance instanceof NavigationItem ? $currentInstance : null;

        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('menu')
            ->setRequired(true)
            ->autocomplete()
            ->setHelp('The item belongs to exactly one navigation menu.')
        ;

        $parentField = AssociationField::new('parent')
            ->setRequired(false)
            ->autocomplete(callback: static function (NavigationItem $candidate): string {
                $menuKey = $candidate->getMenu()?->getMenuKey() ?? '?';

                return sprintf('[%s] %s — %s', $menuKey, $candidate->getNavigationKey(), $candidate->getLabel());
            })
            ->setHelp('Parent must belong to the same menu. Existing items are filtered to that menu; new items show the menu key in autocomplete results.')
            ->hideOnIndex()
        ;

        if (null !== $currentItem?->getMenu()) {
            $menu = $currentItem->getMenu();
            $currentId = $currentItem->getId();
            $parentField->setQueryBuilder(static function (QueryBuilder $queryBuilder) use ($menu, $currentId): QueryBuilder {
                $alias = $queryBuilder->getRootAliases()[0] ?? 'entity';
                $queryBuilder
                    ->andWhere(sprintf('%s.menu = :navigation_parent_menu', $alias))
                    ->setParameter('navigation_parent_menu', $menu)
                    ->addOrderBy(sprintf('%s.position', $alias), 'ASC')
                    ->addOrderBy(sprintf('%s.id', $alias), 'ASC')
                ;

                if (null !== $currentId) {
                    $queryBuilder
                        ->andWhere(sprintf('%s.id <> :navigation_current_item_id', $alias))
                        ->setParameter('navigation_current_item_id', $currentId)
                    ;
                }

                return $queryBuilder;
            });
        }

        yield $parentField;
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

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $builder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $instance = $entityDto->getInstance();

        if ($instance instanceof NavigationItem) {
            $builder->add(self::EXPECTED_VERSION_FIELD, HiddenType::class, [
                'mapped' => false,
                'data' => (string) $instance->getVersion(),
            ]);
        }

        return $builder;
    }

    public function new(AdminContext $context)
    {
        try {
            return parent::new($context);
        } catch (UniqueConstraintViolationException) {
            $this->addFlash('warning', 'This navigation item conflicts with an existing key or slug in the selected menu. Reload the list and try again.');

            return $this->redirectToRoute('ea_navigation_item_index');
        }
    }

    public function edit(AdminContext $context)
    {
        $request = $context->getRequest();
        if ($request->isMethod('POST')) {
            $submitted = $request->request->all($context->getEntity()->getName());
            $expectedVersion = $submitted[self::EXPECTED_VERSION_FIELD] ?? null;
            if (is_scalar($expectedVersion) && ctype_digit((string) $expectedVersion)) {
                $request->attributes->set(self::EXPECTED_VERSION_FIELD, (int) $expectedVersion);
            }
        }

        try {
            return parent::edit($context);
        } catch (OptimisticLockException) {
            $this->addFlash('warning', 'This navigation item was changed by another administrator. Reload it and apply your changes again.');

            return $this->redirectToRoute('ea_navigation_item_index');
        } catch (UniqueConstraintViolationException) {
            $this->addFlash('warning', 'This navigation item conflicts with an existing key or slug in the selected menu. Reload the list and try again.');

            return $this->redirectToRoute('ea_navigation_item_index');
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if (!$entityInstance instanceof NavigationItem) {
            throw new \InvalidArgumentException('NavigationItemCrudController can update only NavigationItem entities.');
        }

        $context = $this->adminContextProvider->getContext();
        $expectedVersion = $context?->getRequest()->attributes->get(self::EXPECTED_VERSION_FIELD);
        if (!is_int($expectedVersion) || $expectedVersion < 1) {
            throw new \RuntimeException('Navigation item edit is missing its optimistic-lock version token. Reload the form and try again.');
        }

        $entityManager->lock($entityInstance, LockMode::OPTIMISTIC, $expectedVersion);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function archiveItem(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $item = $this->resolveNavigationItem($context);
        $this->assertStateChangeRequest($context, $item, 'archiveItem');
        $item->archive();

        try {
            $entityManager->flush();
        } catch (OptimisticLockException) {
            $this->addFlash('warning', 'This navigation item changed while Archive was being applied. Reload the list and try again.');
        }

        return $this->redirectToRoute('ea_navigation_item_index');
    }

    public function restoreItem(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $item = $this->resolveNavigationItem($context);
        $this->assertStateChangeRequest($context, $item, 'restoreItem');
        $item->restore();

        try {
            $entityManager->flush();
        } catch (OptimisticLockException) {
            $this->addFlash('warning', 'This navigation item changed while Restore was being applied. Reload the list and try again.');
        }

        return $this->redirectToRoute('ea_navigation_item_index');
    }

    public function duplicateItem(AdminContext $context, EntityManagerInterface $entityManager): RedirectResponse
    {
        $item = $this->resolveNavigationItem($context);
        $this->assertStateChangeRequest($context, $item, 'duplicateItem');
        $token = date('YmdHis').'-'.bin2hex(random_bytes(5));
        $copy = (new NavigationItem())
            ->setMenu($item->getMenu())
            ->setParent($item->getParent())
            ->setNavigationKey($this->appendWithinLimit($item->getNavigationKey(), '.copy.'.$token, 160))
            ->setLabel($this->appendWithinLimit($item->getLabel(), ' copy', 140))
            ->setSlug(null === $item->getSlug() ? null : $this->appendWithinLimit($item->getSlug(), '-copy-'.$token, 180))
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

        try {
            $entityManager->persist($copy);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            $this->addFlash('warning', 'The navigation item could not be duplicated because a conflicting key or slug was created concurrently. Try Duplicate again.');

            return $this->redirectToRoute('ea_navigation_item_index');
        }

        return $this->redirectToRoute('ea_navigation_item_index');
    }

    private function assertStateChangeRequest(AdminContext $context, NavigationItem $item, string $actionName): void
    {
        $request = $context->getRequest();
        if (!$request->isMethod('POST')) {
            throw $this->createAccessDeniedException('Navigation state-changing actions require POST.');
        }

        $id = $item->getId();
        $submittedToken = $request->request->get('_token');
        if (null === $id || !is_string($submittedToken)) {
            throw $this->createAccessDeniedException('Navigation action CSRF token is missing.');
        }

        $tokenId = sprintf('navigating.item.%s.%d', $actionName, $id);
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($tokenId, $submittedToken))) {
            throw $this->createAccessDeniedException('Navigation action CSRF token is invalid.');
        }
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
