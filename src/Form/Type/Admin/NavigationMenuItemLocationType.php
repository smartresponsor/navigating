<?php

declare(strict_types=1);

namespace App\Navigating\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class NavigationMenuItemLocationType extends AbstractType
{
    /** @return array<string, string> */
    public static function choices(): array
    {
        return [
            'Body top workspace' => 'shell.body.top',
            'Header bottom breadcrumb' => 'shell.header.bottom',
            'Left middle primary' => 'shell.left.middle',
            'Context middle section' => 'shell.context.middle',
            'Main toolbar actions' => 'shell.main.toolbar',
            'Right tool panel' => 'shell.right.tool',
            'Right filter panel' => 'shell.right.filter',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => self::choices(),
            'placeholder' => false,
            'required' => true,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
