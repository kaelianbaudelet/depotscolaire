<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206133738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assignment ADD allow_late_submissions TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('DROP INDEX UNIQ_497D309D77153098 ON classroom');
        $this->addSql('ALTER TABLE classroom DROP code');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classroom ADD code VARCHAR(10) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_497D309D77153098 ON classroom (code)');
        $this->addSql('ALTER TABLE assignment DROP allow_late_submissions');
    }
}
