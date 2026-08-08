<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationConcurrencyContractTest extends TestCase
{
    public function testSnapshotWritersAreSerializedPerTarget(): void
    {
        $writer = self::read('src/Service/Navigation/Snapshot/NavigationSnapshotFileService.php');

        self::assertStringContainsString(".'.lock'", $writer);
        self::assertStringContainsString("fopen(\$lockPath, 'c+b')", $writer);
        self::assertStringContainsString('flock($lockHandle, LOCK_EX)', $writer);
        self::assertStringContainsString('writeLocked($path, $json)', $writer);
    }

    public function testImmutableRecoveryArtifactsAvoidSameSecondFilenameCollisions(): void
    {
        $backup = self::read('src/Command/NavigationBackupCreateCommand.php');
        $legacyPlan = self::read('src/Command/NavigationLegacyMigrationPlanCommand.php');

        self::assertStringContainsString("bin2hex(random_bytes(4))", $backup);
        self::assertStringContainsString("bin2hex(random_bytes(4))", $legacyPlan);
    }

    public function testDoctrineEntitiesUseOptimisticLocking(): void
    {
        $menu = self::read('src/Entity/NavigationMenu.php');
        $item = self::read('src/Entity/NavigationItem.php');

        self::assertStringContainsString('#[ORM\\Version]', $menu);
        self::assertStringContainsString('#[ORM\\Version]', $item);
        self::assertStringContainsString('private int $version = 1;', $menu);
        self::assertStringContainsString('private int $version = 1;', $item);
        self::assertStringContainsString('public function getVersion(): int', $menu);
        self::assertStringContainsString('public function getVersion(): int', $item);
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);

        return $contents;
    }
}
