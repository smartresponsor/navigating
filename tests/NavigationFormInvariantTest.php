<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Form\Extension\NavigationEntityInvariantTypeExtension;
use App\Navigating\Service\Navigation\Persistence\NavigationEntityInvariantService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Forms;

final class NavigationFormInvariantTest extends TestCase
{
    public function testInvalidMenuBecomesFormErrorInsteadOfException(): void
    {
        $menu = (new NavigationMenu())
            ->setMenuKey('Invalid Menu')
            ->setSlug('main')
            ->setLabel('Main')
            ->setLocation('shell.left.middle')
            ->setType('navigation');

        $form = $this->form($menu);
        $form->submit([]);

        self::assertFalse($form->isValid());
        self::assertStringContainsString('lowercase navigation token', (string) $form->getErrors(true, false)[0]->getMessage());
    }

    public function testInvalidTargetBecomesFormErrorInsteadOfException(): void
    {
        $menu = $this->menu();
        $item = (new NavigationItem())
            ->setMenu($menu)
            ->setNavigationKey('vendor')
            ->setLabel('Vendor')
            ->setType('link')
            ->setOperation('index')
            ->setRouteName('vendor_index')
            ->setPath('/vendor');

        $form = $this->form($item);
        $form->submit([]);

        self::assertFalse($form->isValid());
        self::assertStringContainsString('either a route target or a path target', (string) $form->getErrors(true, false)[0]->getMessage());
    }

    public function testValidEntityDoesNotReceiveInvariantFormError(): void
    {
        $form = $this->form($this->menu());
        $form->submit([]);

        self::assertTrue($form->isValid());
        self::assertCount(0, $form->getErrors(true));
    }

    private function form(object $data)
    {
        $invariants = new NavigationEntityInvariantService([
            'shell_locations' => ['shell.left.middle' => []],
        ]);
        $factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new NavigationEntityInvariantTypeExtension($invariants))
            ->getFormFactory();

        return $factory->create(FormType::class, $data);
    }

    private function menu(): NavigationMenu
    {
        return (new NavigationMenu())
            ->setMenuKey('main')
            ->setSlug('main')
            ->setLabel('Main')
            ->setLocation('shell.left.middle')
            ->setType('navigation');
    }
}
