<?php

declare(strict_types=1);

namespace App\Navigating\DataFixtures;

use App\Navigating\Service\Navigation\Import\NavigationConfigImportService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class NavigationFixture extends Fixture
{
    /** @param array<string, mixed> $navigationConfig */
    public function __construct(
        private readonly NavigationConfigImportService $importService,
        private readonly array $navigationConfig = [],
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->importService->replaceFromConfig($this->navigationConfig);
    }
}
