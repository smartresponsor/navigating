<?php

declare(strict_types=1);

namespace App\Navigating\Form\Extension;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Service\Navigation\Persistence\NavigationEntityInvariantService;
use App\Navigating\Service\Navigation\Persistence\NavigationEntityUniquenessService;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class NavigationEntityInvariantTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly NavigationEntityInvariantService $invariants,
        private readonly ?NavigationEntityUniquenessService $uniqueness = null,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            if (!$form->isRoot()) {
                return;
            }

            $entity = $event->getData();
            if (!$entity instanceof NavigationMenu && !$entity instanceof NavigationItem) {
                return;
            }

            try {
                $this->invariants->validate($entity);
                $this->uniqueness?->validate($entity);
            } catch (\DomainException $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }
        });
    }
}
