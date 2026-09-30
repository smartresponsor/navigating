<?php

declare(strict_types=1);

namespace App\Navigating\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the canonical Navigating menu/item schema from Doctrine metadata shape.';
    }

    public function up(Schema $schema): void
    {
        $hasMenu = $schema->hasTable('navigation_menu');
        $hasItem = $schema->hasTable('navigation_item');

        if ($hasMenu && $hasItem) {
            return;
        }

        $this->abortIf(
            $hasMenu || $hasItem,
            'Navigating baseline requires both navigation tables to be absent or both already present.',
        );

        $menu = $schema->createTable('navigation_menu');
        $menu->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $menu->addColumn('version', Types::INTEGER, ['default' => 1]);
        $menu->addColumn('menu_key', Types::STRING, ['length' => 160]);
        $menu->addColumn('slug', Types::STRING, ['length' => 180]);
        $menu->addColumn('label', Types::STRING, ['length' => 140]);
        $menu->addColumn('location', Types::STRING, ['length' => 120]);
        $menu->addColumn('type', Types::STRING, ['length' => 60]);
        $menu->addColumn('visible_for_roles', Types::JSON);
        $menu->addColumn('visible_for_scopes', Types::JSON);
        $menu->addColumn('visible_for_environments', Types::JSON);
        $menu->addColumn('priority', Types::INTEGER);
        $menu->addColumn('enabled', Types::BOOLEAN);
        $menu->addColumn('metadata', Types::JSON);
        $menu->addColumn('created_at', Types::DATETIME_MUTABLE);
        $menu->addColumn('modified_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $menu->addColumn('created_by', Types::STRING, ['length' => 190, 'notnull' => false]);
        $menu->addColumn('modified_by', Types::STRING, ['length' => 190, 'notnull' => false]);
        $menu->setPrimaryKey(['id']);
        $menu->addUniqueIndex(['menu_key'], 'uniq_navigation_menu_key');
        $menu->addUniqueIndex(['slug'], 'uniq_navigation_menu_slug');
        $menu->addIndex(['location', 'enabled', 'priority'], 'idx_navigation_menu_location_enabled_priority');

        $item = $schema->createTable('navigation_item');
        $item->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $item->addColumn('version', Types::INTEGER, ['default' => 1]);
        $item->addColumn('navigation_key', Types::STRING, ['length' => 160]);
        $item->addColumn('label', Types::STRING, ['length' => 140]);
        $item->addColumn('slug', Types::STRING, ['length' => 180, 'notnull' => false]);
        $item->addColumn('route_name', Types::STRING, ['length' => 180, 'notnull' => false]);
        $item->addColumn('route_parameters', Types::JSON);
        $item->addColumn('path_target', Types::STRING, ['length' => 512, 'notnull' => false]);
        $item->addColumn('operation', Types::STRING, ['length' => 60]);
        $item->addColumn('type', Types::STRING, ['length' => 40]);
        $item->addColumn('icon', Types::STRING, ['length' => 80, 'notnull' => false]);
        $item->addColumn('badge', Types::STRING, ['length' => 80, 'notnull' => false]);
        $item->addColumn('visible_for_roles', Types::JSON);
        $item->addColumn('visible_for_scopes', Types::JSON);
        $item->addColumn('visible_for_environments', Types::JSON);
        $item->addColumn('position', Types::INTEGER);
        $item->addColumn('enabled', Types::BOOLEAN);
        $item->addColumn('metadata', Types::JSON);
        $item->addColumn('archived_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $item->addColumn('created_at', Types::DATETIME_MUTABLE);
        $item->addColumn('modified_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $item->addColumn('created_by', Types::STRING, ['length' => 190, 'notnull' => false]);
        $item->addColumn('modified_by', Types::STRING, ['length' => 190, 'notnull' => false]);
        $item->addColumn('menu_id', Types::INTEGER);
        $item->addColumn('parent_id', Types::INTEGER, ['notnull' => false]);
        $item->setPrimaryKey(['id']);
        $item->addUniqueIndex(['menu_id', 'navigation_key'], 'uniq_navigation_item_menu_key');
        $item->addUniqueIndex(['menu_id', 'slug'], 'uniq_navigation_item_menu_slug');
        $item->addIndex(['menu_id', 'position'], 'idx_navigation_item_menu_position');
        $item->addIndex(['parent_id', 'position'], 'idx_navigation_item_parent_position');
        $item->addIndex(['route_name'], 'idx_navigation_item_route_name');
        $item->addIndex(['operation'], 'idx_navigation_item_operation');
        $item->addIndex(['archived_at'], 'idx_navigation_item_archived_at');
        $item->addIndex(['enabled', 'position'], 'idx_navigation_item_enabled_position');
        $item->addIndex(['menu_id'], 'IDX_289BF06CCCD7E912');
        $item->addIndex(['parent_id'], 'IDX_289BF06C727ACA70');
        $item->addForeignKeyConstraint('navigation_menu', ['menu_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_289BF06CCCD7E912');
        $item->addForeignKeyConstraint('navigation_item', ['parent_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_289BF06C727ACA70');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('navigation_item')) {
            $schema->dropTable('navigation_item');
        }
        if ($schema->hasTable('navigation_menu')) {
            $schema->dropTable('navigation_menu');
        }
    }
}
