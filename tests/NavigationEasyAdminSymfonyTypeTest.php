<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationEasyAdminSymfonyTypeTest extends TestCase
{
    public function testEasyAdminCrudUsesSymfonyFormTypes(): void
    {
        $controller = self::read('src/Controllers/Admin/NavigationMenuItemCrudController.php');

        self::assertStringContainsString('JsonArrayTextareaType::class', $controller);
        self::assertStringContainsString('NavigationMenuItemLocationType::class', $controller);
        self::assertStringContainsString('NavigationMenuItemOperationType::class', $controller);
        self::assertStringContainsString('->setFormType(', $controller);
        self::assertStringNotContainsString('->setChoices([', $controller);
    }

    public function testSymfonyFormTypesExistForEasyAdminCrudFields(): void
    {
        self::assertFileExists(self::path('src/Form/Type/Admin/NavigationMenuItemLocationType.php'));
        self::assertFileExists(self::path('src/Form/Type/Admin/NavigationMenuItemOperationType.php'));
        self::assertFileExists(self::path('src/Form/Type/Admin/JsonArrayTextareaType.php'));

        self::assertStringContainsString('extends AbstractType', self::read('src/Form/Type/Admin/NavigationMenuItemLocationType.php'));
        self::assertStringContainsString('ChoiceType::class', self::read('src/Form/Type/Admin/NavigationMenuItemLocationType.php'));
        self::assertStringContainsString('ChoiceType::class', self::read('src/Form/Type/Admin/NavigationMenuItemOperationType.php'));
        self::assertStringContainsString('TextareaType::class', self::read('src/Form/Type/Admin/JsonArrayTextareaType.php'));
        self::assertStringContainsString('CallbackTransformer', self::read('src/Form/Type/Admin/JsonArrayTextareaType.php'));
    }

    public function testComposerDeclaresSymfonyFormLayer(): void
    {
        $composer = json_decode(self::read('composer.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('symfony/form', $composer['require']);
        self::assertArrayHasKey('symfony/options-resolver', $composer['require']);
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(self::path($relativePath));
        self::assertIsString($contents);

        return $contents;
    }

    private static function path(string $relativePath): string
    {
        return dirname(__DIR__).'/'.$relativePath;
    }
}
