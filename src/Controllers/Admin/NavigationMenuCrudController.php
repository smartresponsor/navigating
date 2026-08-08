<?php

declare(strict_types=1);

namespace App\Navigating\Controllers\Admin;

use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Form\Type\Admin\JsonArrayTextareaType;
use App\Navigating\Form\Type\Admin\JsonListTextareaType;
use App\Navigating\Form\Type\Admin\NavigationItemLocationType;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class NavigationMenuCrudController extends AbstractCrudController
{
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
        yield HiddenField::new('version')->onlyWhenUpdating();
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

    public function edit(AdminContext $context)
    {
        try {
            return parent::edit($context);
        } catch (OptimisticLockException) {
            $this->addFlash('warning', 'This navigation menu was changed by another administrator. Reload it and apply your changes again.');

            return $this->redirectToRoute('ea_navigation_menu_index');
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if (!$entityInstance instanceof NavigationMenu) {
            throw new \InvalidArgumentException('NavigationMenuCrudController can update only NavigationMenu entities.');
        }

        $entityManager->lock($entityInstance, LockMode::OPTIMISTIC, $entityInstance->getVersion());
        parent::updateEntity($entityManager, $entityInstance);
    }
}
