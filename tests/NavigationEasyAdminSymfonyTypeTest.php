<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationEasyAdminSymfonyTypeTest extends TestCase
{
    public function testEasyAdminCrudUsesSymfonyFormTypeBoundaries(): void
    {
        $menuController = self::read('src/Controllers/Admin/NavigationMenuCrudController.php');
        $itemController = self::read('src/Controllers/Admin/NavigationItemCrudController.php');

        self::assertStringContainsString('NavigationItemLocationType::class', $menuController);
        self::assertStringContainsString('JsonArrayTextareaType::class', $menuController);
        self::assertStringContainsString('JsonListTextareaType::class', $menuController);
        self::assertStringContainsString('JsonArrayTextareaType::class', $itemController);
        self::assertStringContainsString('JsonListTextareaType::class', $itemController);
        self::assertStringContainsString('NavigationItemOperationType::class', $itemController);
        self::assertStringContainsString('->setFormType(', $menuController.$itemController);
        self::assertStringNotContainsString('->setChoices([', $menuController.$itemController);
    }

    public function testSymfonyFormTypesRemainAvailable(): void
    {
        self::assertFileExists(self::path('src/Form/Type/Admin/NavigationItemLocationType.php'));
        self::assertFileExists(self::path('src/Form/Type/Admin/NavigationItemOperationType.php'));
        self::assertFileExists(self::path('src/Form/Type/Admin/JsonArrayTextareaType.php'));
        self::assertFileExists(self::path('src/Form/Type/Admin/JsonListTextareaType.php'));

        self::assertStringContainsString('extends AbstractType', self::read('src/Form/Type/Admin/NavigationItemLocationType.php'));
        self::assertStringContainsString('ChoiceType::class', self::read('src/Form/Type/Admin/NavigationItemLocationType.php'));
        self::assertStringContainsString('ChoiceType::class', self::read('src/Form/Type/Admin/NavigationItemOperationType.php'));
        self::assertStringContainsString('TextareaType::class', self::read('src/Form/Type/Admin/JsonArrayTextareaType.php'));
        self::assertStringContainsString('CallbackTransformer', self::read('src/Form/Type/Admin/JsonArrayTextareaType.php'));
        self::assertStringContainsString('array_is_list', self::read('src/Form/Type/Admin/JsonListTextareaType.php'));
    }

    public function testComposerKeepsPlatformDependenciesExplicit(): void
    {
        $composer = json_decode(self::read('composer.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('easycorp/easyadmin-bundle', $composer['require']);
        self::assertArrayHasKey('objecting/object', $composer['require']);
        self::assertArrayHasKey('cruding/crud', $composer['require']);
        self::assertArrayHasKey('interfacing/interface', $composer['require']);
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
