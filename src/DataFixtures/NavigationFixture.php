<?php

declare(strict_types=1);

namespace App\Navigating\DataFixtures;

use App\Navigating\Service\Navigation\Import\NavigationConfigImportService;
use App\Navigating\Service\Navigation\Snapshot\NavigationSnapshotService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class NavigationFixture extends Fixture
{
    /** @param array<string, mixed> $navigationConfig */
    public function __construct(
        private readonly NavigationConfigImportService $importService,
        private readonly NavigationSnapshotService $snapshotService,
        private readonly array $navigationConfig = [],
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $manifest = dirname(__DIR__, 2).'/resources/navigation/navigation.install.json';

        if (is_file($manifest)) {
            $contents = file_get_contents($manifest);
            if (!is_string($contents)) {
                throw new \RuntimeException('Unable to read navigation install manifest.');
            }
            $snapshot = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($snapshot)) {
                throw new \RuntimeException('Navigation install manifest must decode to an object.');
            }
            $this->snapshotService->restore($snapshot);
            return;
        }

        $this->importService->replaceFromConfig($this->navigationConfig);
    }
}
