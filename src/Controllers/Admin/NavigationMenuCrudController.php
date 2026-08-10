<?php

declare(strict_types=1);

namespace App\Navigating\Controllers\Admin;

use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Form\Type\Admin\JsonArrayTextareaType;
use App\Navigating\Form\Type\Admin\JsonListTextareaType;
use App\Navigating\Form\Type\Admin\NavigationItemLocationType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class NavigationMenuCrudController extends AbstractCrudController
{
    private const EXPECTED_VERSION_FIELD = '_navigation_expected_version';

    public function __construct(private readonly AdminContextProvider $adminContextProvider)
    {
    }

    public static function getEntityFqcn(): string
    {
        return NavigationMenu::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Navigation menu')
            ->setEntityLabelInPlural('Navigation menus')
            ->setPageTitle(Crud::PAGE_INDEX, 'Navigation menus')
            ->setDefaultSort(['priority' => 'ASC', 'id' => 'ASC'])
            ->setSearchFields(['menuKey', 'slug', 'label', 'location', 'type'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('menuKey')->setHelp('Stable application key, for example left_business or attachment_context.');
        yield TextField::new('slug');
        yield TextField::new('label');
        yield TextField::new('location')->setFormType(NavigationItemLocationType::class);
        yield TextField::new('type');
        yield TextareaField::new('visibleForRoles')
            ->setFormType(JsonListTextareaType::class)
            ->setHelp('JSON string list, for example ["ROLE_ADMIN", "ROLE_SUPER_ADMIN"].')
            ->hideOnIndex()
        ;
        yield TextareaField::new('visibleForScopes')
            ->setFormType(JsonListTextareaType::class)
            ->hideOnIndex()
        ;
        yield TextareaField::new('visibleForEnvironments')
            ->setFormType(JsonListTextareaType::class)
            ->hideOnIndex()
        ;
        yield IntegerField::new('priority');
        yield BooleanField::new('enabled');
        yield TextareaField::new('metadata')
            ->setFormType(JsonArrayTextareaType::class)
            ->setHelp('JSON object with navigation/runtime metadata.')
            ->hideOnIndex()
        ;
        yield DateTimeField::new('objectCreatedAt')->hideOnForm();
        yield DateTimeField::new('objectModifiedAt')->hideOnForm();
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $builder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $instance = $entityDto->getInstance();

        if ($instance instanceof NavigationMenu) {
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
            $this->addFlash('warning', 'This navigation menu conflicts with an existing menu key or slug. Reload the list and try again.');

            return $this->redirectToRoute('ea_navigation_menu_index');
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
            $this->addFlash('warning', 'This navigation menu was changed by another administrator. Reload it and apply your changes again.');

            return $this->redirectToRoute('ea_navigation_menu_index');
        } catch (UniqueConstraintViolationException) {
            $this->addFlash('warning', 'This navigation menu conflicts with an existing menu key or slug. Reload the list and try again.');

            return $this->redirectToRoute('ea_navigation_menu_index');
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if (!$entityInstance instanceof NavigationMenu) {
            throw new \InvalidArgumentException('NavigationMenuCrudController can update only NavigationMenu entities.');
        }

        $context = $this->adminContextProvider->getContext();
        $expectedVersion = $context?->getRequest()->attributes->get(self::EXPECTED_VERSION_FIELD);
        if (!is_int($expectedVersion) || $expectedVersion < 1) {
            throw new \RuntimeException('Navigation menu edit is missing its optimistic-lock version token. Reload the form and try again.');
        }

        $entityManager->lock($entityInstance, LockMode::OPTIMISTIC, $expectedVersion);
        parent::updateEntity($entityManager, $entityInstance);
    }
}
