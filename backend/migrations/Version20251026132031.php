<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251026132031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite unique (idempotency_key, command_class) and drop unique on idempotency_key';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        if ($platform === 'postgresql') {
            // Drop existing unique index if present
            $this->addSql("DO $$ BEGIN IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'UNIQ_idempotency_keys_idempotency_key') THEN EXECUTE 'DROP INDEX IF EXISTS \"UNIQ_idempotency_keys_idempotency_key\"'; END IF; END $$;");
            // Add composite unique
            $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_idem_key_context ON idempotency_keys (idempotency_key, command_class)');
        } elseif ($platform === 'mysql') {
            $this->addSql('ALTER TABLE idempotency_keys DROP INDEX idempotency_key');
            $this->addSql('CREATE UNIQUE INDEX uniq_idem_key_context ON idempotency_keys (idempotency_key, command_class)');
        } else {
            // Fallback generic SQL
            $this->addSql('CREATE UNIQUE INDEX uniq_idem_key_context ON idempotency_keys (idempotency_key, command_class)');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        if ($platform === 'postgresql') {
            $this->addSql('DROP INDEX IF EXISTS uniq_idem_key_context');
            // Optionally recreate unique on idempotency_key only
            $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_idempotency_keys_idempotency_key ON idempotency_keys (idempotency_key)');
        } elseif ($platform === 'mysql') {
            $this->addSql('DROP INDEX uniq_idem_key_context ON idempotency_keys');
            $this->addSql('CREATE UNIQUE INDEX idempotency_key ON idempotency_keys (idempotency_key)');
        } else {
            $this->addSql('DROP INDEX uniq_idem_key_context');
        }
    }
}
