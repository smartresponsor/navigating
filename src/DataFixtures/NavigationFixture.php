<?php

declare(strict_types=1);

namespace App\Navigating\DataFixtures;

use App\Navigating\Service\Navigation\Import\NavigationConfigImportService;
use App\Navigating\Service\Navigation\Snapshot\NavigationSnapshotService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class NavigationFixture extends Fixture implements FixtureGroupInterface
{
    /** @param array<string, mixed> $navigationConfig */
    public function __construct(
        private readonly NavigationConfigImportService $importService,
        private readonly NavigationSnapshotService $snapshotService,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        private readonly array $navigationConfig = [],
    ) {
    }

    /** @return list<string> */
    public static function getGroups(): array
    {
        return ['navigating'];
    }

    public function load(ObjectManager $manager): void
    {
        $manifestPath = $this->projectDir.'/resources/navigation/navigation.install.json';
        if (is_file($manifestPath)) {
            $contents = file_get_contents($manifestPath);
            if (!is_string($contents)) {
                throw new \RuntimeException('Unable to read Navigating install manifest.');
            }

            $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($manifest)) {
                throw new \RuntimeException('Navigating install manifest must decode to an object.');
            }

            $this->snapshotService->restore($manifest);

            return;
        }

        $this->importService->replaceFromConfig($this->navigationConfig);
    }
}
