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
        self::assertStringContainsString('vendor/objecting/object/src/Embeddable', $doctrine);
        self::assertStringContainsString("prefix: 'App\\Objecting\\Embeddable'", $doctrine);
    }

    public function testStandaloneKernelBootsEasyAdminAndLoadsAdminAttributes(): void
    {
        $kernel = self::read('src/Kernel/NavigationKernel.php');
        $routes = self::read('config/routes_dev.yaml');
        $security = self::read('config/standalone/security.yaml');

        self::assertStringContainsString('use EasyCorp\\Bundle\\EasyAdminBundle\\EasyAdminBundle;', $kernel);
        self::assertStringContainsString('yield new EasyAdminBundle();', $kernel);
        self::assertStringContainsString("resource: '../src/Controllers/Admin/'", $routes);
        self::assertStringContainsString('type: attribute', $routes);
        self::assertStringContainsString("prefix: '/%app.back_token%'", $routes);
        self::assertStringContainsString("app.default_back_token: 'ea'", $security);
        self::assertStringContainsString("path: '^/%app.back_token%'", $security);
    }

    public function testServiceDiscoveryRegistersControllersAndFormTypesWithoutTreatingTechnicalClassesAsServices(): void
    {
        $services = self::read('config/services.yaml');
        $formType = self::read('src/Form/Type/Admin/JsonListTextareaType.php');

        self::assertStringContainsString("resource: '../src/'", $services);
        self::assertStringContainsString("- '../src/DataFixtures/'", $services);
        self::assertStringContainsString("- '../src/DependencyInjection/'", $services);
        self::assertStringContainsString("- '../src/Entity/'", $services);
        self::assertStringContainsString("- '../src/Kernel/'", $services);
        self::assertStringContainsString('App\\Navigating\\Controllers\\:', $services);
        self::assertStringContainsString("resource: '../src/Controllers/'", $services);
        self::assertStringContainsString('controller.service_arguments', $services);
        self::assertStringContainsString('extends AbstractType', $formType);
        self::assertStringContainsString('autoconfigure: true', $services);
    }

    public function testDevOnlyFixturesAreLoadedConditionally(): void
    {
        $runtimeServices = self::read('config/services.yaml');
        $fixtureServices = self::read('config/services_fixtures.yaml');
        $extension = self::read('src/DependencyInjection/NavigationRuntimeExtension.php');

        self::assertStringContainsString("- '../src/DataFixtures/'", $runtimeServices);
        self::assertStringContainsString('App\\Navigating\\DataFixtures\\:', $fixtureServices);
        self::assertStringContainsString("resource: '../src/DataFixtures/'", $fixtureServices);
        self::assertStringContainsString('class_exists(\\Doctrine\\Bundle\\FixturesBundle\\Fixture::class)', $extension);
        self::assertStringContainsString("load('services_fixtures.yaml')", $extension);
    }

    public function testCiHasProductionNoDevBootAndWarmupGate(): void
    {
        $workflow = self::read('.github/workflows/sqlite-recovery.yml');
        $productionStart = strpos($workflow, '  production-no-dev:');
        self::assertIsInt($productionStart);

        $productionJob = substr($workflow, $productionStart);
        self::assertIsString($productionJob);
        self::assertStringContainsString('APP_ENV: prod', $productionJob);
        self::assertStringContainsString('APP_DEBUG:', $productionJob);
        self::assertStringContainsString('composer install --no-dev', $productionJob);
        self::assertStringContainsString('php bin/console lint:container', $productionJob);
        self::assertStringContainsString('cache:clear --no-warmup --env=prod --no-debug', $productionJob);
        self::assertStringContainsString('cache:warmup --env=prod --no-debug', $productionJob);
        self::assertStringContainsString('php bin/console navigation:database:update', $productionJob);
        self::assertStringContainsString('php bin/console doctrine:schema:validate', $productionJob);

        $warmupPosition = strpos($productionJob, 'cache:warmup --env=prod --no-debug');
        $schemaPosition = strpos($productionJob, 'php bin/console navigation:database:update');
        self::assertIsInt($warmupPosition);
        self::assertIsInt($schemaPosition);
        self::assertLessThan($schemaPosition, $warmupPosition, 'Production cache warmup must be proven safe before navigation schema creation.');
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
