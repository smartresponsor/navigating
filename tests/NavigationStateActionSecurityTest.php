<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationStateActionSecurityTest extends TestCase
{
    public function testStateActionsUsePostFormsAndExplicitCsrfValidation(): void
    {
        $controller = self::read('src/Controllers/Admin/NavigationItemCrudController.php');
        $template = self::read('templates/admin/action/navigation_item_state_change.html.twig');
        $composer = self::read('composer.json');
        $framework = self::read('config/standalone/framework.yaml');

        self::assertStringNotContainsString('displayAsButton()', $controller);
        self::assertSame(3, substr_count($controller, '->renderAsForm()'));
        self::assertSame(3, substr_count($controller, '->setTemplatePath(self::STATE_ACTION_TEMPLATE)'));
        self::assertStringContainsString("private const STATE_ACTION_TEMPLATE = '@Navigating/admin/action/navigation_item_state_change.html.twig';", $controller);

        self::assertStringContainsString("if (!\$request->isMethod('POST'))", $controller);
        self::assertStringContainsString('CsrfTokenManagerInterface', $controller);
        self::assertStringContainsString("sprintf('navigating.item.%s.%d', \$actionName, \$id)", $controller);
        self::assertStringContainsString('isTokenValid(new CsrfToken($tokenId, $submittedToken))', $controller);

        self::assertStringContainsString('method="post"', $template);
        self::assertStringContainsString('name="_token"', $template);
        self::assertStringContainsString("csrf_token('navigating.item.' ~ action.name ~ '.' ~ entity.primaryKeyValue)", $template);

        foreach (['archiveItem', 'restoreItem', 'duplicateItem'] as $action) {
            self::assertStringContainsString("assertStateChangeRequest(\$context, \$item, '".$action."')", $controller);
        }

        self::assertStringContainsString('"symfony/security-csrf": "^8.1"', $composer);
        self::assertStringContainsString('csrf_protection: true', $framework);
        self::assertStringContainsString('session: true', $framework);
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);

        return $contents;
    }
}
