<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250603120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add idempotency_keys table for command idempotency support';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        
        if ($platform === 'postgresql') {
            $this->addSql('CREATE TABLE idempotency_keys (
                id UUID NOT NULL,
                idempotency_key VARCHAR(255) NOT NULL,
                command_class VARCHAR(255) NOT NULL,
                result JSON NOT NULL,
                status VARCHAR(50) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
            )');
            $this->addSql('COMMENT ON COLUMN idempotency_keys.id IS \'(DC2Type:uuid)\'');
            $this->addSql('COMMENT ON COLUMN idempotency_keys.created_at IS \'(DC2Type:datetime_immutable)\'');
            $this->addSql('COMMENT ON COLUMN idempotency_keys.completed_at IS \'(DC2Type:datetime_immutable)\'');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_idempotency_key ON idempotency_keys (idempotency_key)');
            $this->addSql('CREATE INDEX idx_idempotency_key ON idempotency_keys (idempotency_key)');
            $this->addSql('CREATE INDEX idx_created_at ON idempotency_keys (created_at)');
        } else {
            $this->addSql('CREATE TABLE idempotency_keys (
                id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\',
                idempotency_key VARCHAR(255) NOT NULL,
                command_class VARCHAR(255) NOT NULL,
                result JSON NOT NULL,
                status VARCHAR(50) NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY(id),
                UNIQUE INDEX UNIQ_idempotency_key (idempotency_key),
                INDEX idx_idempotency_key (idempotency_key),
                INDEX idx_created_at (created_at)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE idempotency_keys');
    }
}
