<?php

declare(strict_types=1);

namespace App\Navigating\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class NavigationItemLocationType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => true,
            'attr' => [
                'placeholder' => 'shell.left.middle',
                'data-navigation-location-contract' => 'config-owned',
            ],
            'help' => 'Use a location declared under navigation.shell_locations.',
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
