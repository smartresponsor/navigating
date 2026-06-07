<?php

declare(strict_types=1);

namespace App\Navigating\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class NavigationItemOperationType extends AbstractType
{
    /** @return array<string, string> */
    public static function choices(): array
    {
        return [
            'Index' => 'index',
            'Show' => 'show',
            'New' => 'new',
            'Create' => 'create',
            'Edit' => 'edit',
            'Update' => 'update',
            'Delete' => 'delete',
            'Bulk' => 'bulk',
            'Import' => 'import',
            'Export' => 'export',
            'Archive' => 'archive',
            'Restore' => 'restore',
            'Duplicate' => 'duplicate',
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
