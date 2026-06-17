<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationEasyAdminBoundaryTest extends TestCase
{
    public function testNativeEasyAdminBoundaryRemainsFocused(): void
    {
        self::assertFileExists(__DIR__.'/../src/Controllers/Admin/DashboardController.php');
        self::assertFileExists(__DIR__.'/../src/Controllers/Admin/NavigationItemCrudController.php');
        self::assertFileExists(__DIR__.'/../config/routes/easyadmin.yaml');

        self::assertFalse(is_file(__DIR__.'/../src/Controllers/Admin/NavigationCrudRouteController.php'));
        self::assertFalse(is_file(__DIR__.'/../config/routes/navigation_admin_crud.yaml'));
    }
}
