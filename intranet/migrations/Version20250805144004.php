<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250805144004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_evaluation_request DROP FOREIGN KEY FK_AED66974114E77B1');
        $this->addSql('DROP INDEX IDX_AED66974114E77B1 ON project_evaluation_request');
        $this->addSql('ALTER TABLE project_evaluation_request DROP eval_slot_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_evaluation_request ADD eval_slot_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_evaluation_request ADD CONSTRAINT FK_AED66974114E77B1 FOREIGN KEY (eval_slot_id) REFERENCES eval_slot (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_AED66974114E77B1 ON project_evaluation_request (eval_slot_id)');
    }
}
