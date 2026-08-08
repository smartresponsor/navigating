<?php

declare(strict_types=1);

namespace App\Navigating\Controllers\Admin;

use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Form\Type\Admin\JsonArrayTextareaType;
use App\Navigating\Form\Type\Admin\NavigationItemLocationType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
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
}
