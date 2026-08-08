<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationLegacyMigrationContractTest extends TestCase
{
    public function testLegacyUpgradeRequiresPlanAndForceAndRetainsRecoveryArtifacts(): void
    {
        $plan = self::read('src/Command/NavigationLegacyMigrationPlanCommand.php');
        $upgrade = self::read('src/Command/NavigationLegacyMigrationUpgradeCommand.php');
        $service = self::read('src/Service/Navigation/Migration/NavigationLegacyMigrationPlanService.php');

        self::assertStringContainsString("name: 'navigation:database:legacy-plan'", $plan);
        self::assertStringContainsString('Database was not modified.', $plan);
        self::assertStringContainsString("format' => 'smartresponsor.navigation.legacy-upgrade-plan'", $plan);
        self::assertStringContainsString("name: 'navigation:database:legacy-upgrade'", $upgrade);
        self::assertStringContainsString("addOption('force'", $upgrade);
        self::assertStringContainsString('Refusing legacy schema upgrade without --force.', $upgrade);
        self::assertStringContainsString('hash_equals', $upgrade);
        self::assertStringContainsString('raw_rows', $upgrade);
        self::assertStringContainsString('Legacy navigation data changed after the migration plan was created.', $upgrade);
        self::assertStringContainsString('navigation_item_legacy_w31', $upgrade);
        self::assertStringContainsString('restoreLegacySchema', $upgrade);
        self::assertStringContainsString('instanceof SQLitePlatform', $upgrade);
        self::assertStringContainsString('assertNoExternalSqliteDependencies', $upgrade);
        self::assertStringContainsString('captureLegacySchemaObjects', $upgrade);
        self::assertStringContainsString('dropLegacySchemaObjects', $upgrade);
        self::assertStringContainsString("PRAGMA foreign_keys = OFF", $upgrade);
        self::assertStringContainsString('PRAGMA foreign_key_check', $upgrade);
        self::assertStringContainsString('restoreForeignKeyPragma', $upgrade);
        self::assertStringContainsString("type IN ('index', 'trigger')", $upgrade);
        self::assertStringContainsString("isset(\$columns['parent_key'], \$columns['location'], \$columns['required_role'], \$columns['created_at'], \$columns['updated_at'])", $service);
        self::assertStringContainsString('crosses future menu boundaries', $service);
        self::assertStringContainsString('Refusing lossy migration.', $service);
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);

        return $contents;
    }
}
