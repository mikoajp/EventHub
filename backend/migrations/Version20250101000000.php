<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to ensure enum compatibility for Event, Ticket, and Order status columns
 * Note: Since we're using string-backed enums with the same values as before,
 * no actual schema changes are needed. This migration is a placeholder for documentation.
 */
final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add enum type support for Event, Ticket, and Order status columns';
    }

    public function up(Schema $schema): void
    {
        // No schema changes needed - Doctrine will handle enum serialization
        // The status columns remain as VARCHAR/string types in the database
        // Doctrine EnumType automatically converts between enum objects and their string values
    }

    public function down(Schema $schema): void
    {
        // No schema changes needed
    }
}
