<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationAcceptanceContractTest extends TestCase
{
    public function testRuntimeDependenciesAreDeclaredDirectly(): void
    {
        $composer = $this->composer();
        $require = $composer['require'] ?? [];

        self::assertSame('^4.0', $require['doctrine/dbal'] ?? null);
        self::assertSame('^8.1', $require['symfony/form'] ?? null);
    }

    public function testStandaloneDoctrineMapsNavigatingAndObjectingEmbeddables(): void
    {
        $doctrine = self::read('config/standalone/doctrine.yaml');

        self::assertStringContainsString("prefix: 'App\\Navigating\\Entity'", $doctrine);
        self::assertStringContainsString("vendor/objecting/object/src/Embeddable", $doctrine);
        self::assertStringContainsString("prefix: 'App\\Objecting\\Embeddable'", $doctrine);
    }

    public function testAcceptancePreflightIsNonDestructive(): void
    {
        $scripts = $this->composer()['scripts'] ?? [];
        $preflight = $scripts['navigation:acceptance:preflight'] ?? null;

        self::assertSame([
            '@php bin/console lint:container',
            '@navigation:legacy:plan',
            '@qa',
        ], $preflight);

        $serialized = json_encode($preflight, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('legacy-upgrade', $serialized);
        self::assertStringNotContainsString('manifest:write', $serialized);
        self::assertStringNotContainsString('--force', $serialized);
    }

    public function testAcceptanceVerifyChecksSchemaManifestAndQa(): void
    {
        $scripts = $this->composer()['scripts'] ?? [];

        self::assertSame([
            '@php bin/console doctrine:schema:validate',
            '@php bin/console navigation:manifest:verify',
            '@qa',
        ], $scripts['navigation:acceptance:verify'] ?? null);
    }

    /** @return array<string, mixed> */
    private function composer(): array
    {
        $decoded = json_decode(self::read('composer.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);

        return $contents;
    }
}
