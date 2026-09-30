<?php

declare(strict_types=1);

namespace App\Navigating\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926233000 extends AbstractMigration
{
    private const TABLES = ['navigation_menu', 'navigation_item'];

    private const AUDIT_COLUMNS = [
        'object_created_at' => 'created_at',
        'object_modified_at' => 'modified_at',
        'object_created_by' => 'created_by',
        'object_modified_by' => 'modified_by',
    ];

    public function getDescription(): string
    {
        return 'Adopt canonical Objecting audit column names for Navigating entities.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            return;
        }

        foreach (self::TABLES as $table) {
            $this->abortIf(!$this->tableExists($table), sprintf('Required Navigating table %s is missing.', $table));

            foreach (self::AUDIT_COLUMNS as $legacy => $canonical) {
                $legacyExists = $this->columnExists($table, $legacy);
                $canonicalExists = $this->columnExists($table, $canonical);

                if ($legacyExists && !$canonicalExists) {
                    $this->addSql(sprintf('ALTER TABLE %s RENAME COLUMN %s TO %s', $table, $legacy, $canonical));
                    continue;
                }

                $this->abortIf(
                    !$legacyExists && !$canonicalExists,
                    sprintf('Neither legacy nor canonical audit column exists: %s.%s / %s.', $table, $legacy, $canonical),
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Canonical Objecting audit names are a hard-cut contract and are not reverted automatically.',
        );
    }

    private function tableExists(string $table): bool
    {
        return 1 === (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?",
            [$table],
        );
    }

    private function columnExists(string $table, string $column): bool
    {
        return 1 === (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?",
            [$table, $column],
        );
    }
}
