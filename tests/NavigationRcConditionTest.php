<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationRcConditionTest extends TestCase
{
    public function testProjectOwnedRedundantTokenIsRemovedFromSourceAndConfig(): void
    {
        foreach (self::projectFiles() as $file) {
            $contents = self::read($file);

            self::assertStringNotContainsString('Navigation'.self::chars(77, 101, 110, 117), $contents, $file);
            self::assertStringNotContainsString('navigation.'.self::chars(109, 101, 110, 117).'.', $contents, $file);
            self::assertStringNotContainsString('/navigation/'.self::chars(109, 101, 110, 117), $contents, $file);
            self::assertStringNotContainsString('navigation_'.self::chars(109, 101, 110, 117), $contents, $file);
            self::assertStringNotContainsString(self::chars(109, 101, 110, 117).'_key', $contents, $file);
            self::assertStringNotContainsString(self::chars(109, 101, 110, 117).'Key', $contents, $file);
        }
    }

    public function testNoLegacyRuntimeProviderTestReferencesRemain(): void
    {
        foreach (self::projectFiles() as $file) {
            $contents = self::read($file);

            self::assertStringNotContainsString('NavigationRuntimeProvider', $contents, $file);
            self::assertStringNotContainsString('NavigationTreeBuilder', $contents, $file);
            self::assertStringNotContainsString('NavigationConfigNormalizer', $contents, $file);
            self::assertStringNotContainsString('NavigationConfigValidator', $contents, $file);
            self::assertStringNotContainsString('NavigationRoleVisibilityFilter', $contents, $file);
            self::assertStringNotContainsString('NavigationTargetResolver', $contents, $file);
        }
    }

    public function testNoMigrationOrTemporaryQaFilesExist(): void
    {
        $root = dirname(__DIR__);

        self::assertDirectoryDoesNotExist($root.'/migrations');
        self::assertDirectoryDoesNotExist($root.'/src/Migrations');
        self::assertFileDoesNotExist($root.'/.phpunit.result.cache');

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            self::assertStringEndsNotWith('.tmp', $file->getPathname());
        }
    }

    /** @return list<string> */
    private static function projectFiles(): array
    {
        $root = dirname(__DIR__);
        $paths = [];

        foreach (['src', 'config', 'tests'] as $dir) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/'.$dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                if (!in_array($file->getExtension(), ['php', 'yaml', 'yml', 'json'], true)) {
                    continue;
                }

                $paths[] = substr($file->getPathname(), strlen($root) + 1);
            }
        }

        sort($paths);

        return $paths;
    }

    private static function chars(int ...$codes): string
    {
        return implode('', array_map(static fn (int $code): string => chr($code), $codes));
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);

        return $contents;
    }
}
