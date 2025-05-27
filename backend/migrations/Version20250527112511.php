<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add remaining_quantity and created_at columns to ticket_type table
 * and change price column type to NUMERIC(10, 2).
 */
final class Version20250527112511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add remaining_quantity and created_at columns to ticket_type table and change price to NUMERIC(10, 2)';
    }

    public function up(Schema $schema): void
    {
        // Add remaining_quantity column as nullable initially
        $this->addSql('ALTER TABLE ticket_type ADD remaining_quantity INT');
        // Set remaining_quantity to quantity for existing rows
        $this->addSql('UPDATE ticket_type SET remaining_quantity = quantity WHERE remaining_quantity IS NULL');
        // Apply NOT NULL constraint
        $this->addSql('ALTER TABLE ticket_type ALTER COLUMN remaining_quantity SET NOT NULL');

        // Add created_at column with default value
        $this->addSql('ALTER TABLE ticket_type ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP');
        // Add comment for created_at
        $this->addSql('COMMENT ON COLUMN ticket_type.created_at IS \'(DC2Type:datetime_immutable)\'');

        // Change price column type to NUMERIC(10, 2)
        $this->addSql('ALTER TABLE ticket_type ALTER price TYPE NUMERIC(10, 2)');
    }

    public function down(Schema $schema): void
    {
        // Drop remaining_quantity and created_at columns
        $this->addSql('ALTER TABLE ticket_type DROP remaining_quantity');
        $this->addSql('ALTER TABLE ticket_type DROP created_at');
        // Revert price column type to INT
        $this->addSql('ALTER TABLE ticket_type ALTER price TYPE INT');
    }
}