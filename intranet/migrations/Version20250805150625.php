<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250805150625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_evaluation_request (id INT AUTO_INCREMENT NOT NULL, requester_id INT NOT NULL, evaluator_id INT DEFAULT NULL, project_id INT NOT NULL, validated TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, evaluated_at DATETIME DEFAULT NULL, INDEX IDX_AED66974ED442CF4 (requester_id), INDEX IDX_AED6697443575BE2 (evaluator_id), INDEX IDX_AED66974166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_evaluation_request ADD CONSTRAINT FK_AED66974ED442CF4 FOREIGN KEY (requester_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE project_evaluation_request ADD CONSTRAINT FK_AED6697443575BE2 FOREIGN KEY (evaluator_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE project_evaluation_request ADD CONSTRAINT FK_AED66974166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE user_project ADD validated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_project ADD CONSTRAINT FK_77BECEE4C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_77BECEE4C69DE5E5 ON user_project (validated_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_evaluation_request DROP FOREIGN KEY FK_AED66974ED442CF4');
        $this->addSql('ALTER TABLE project_evaluation_request DROP FOREIGN KEY FK_AED6697443575BE2');
        $this->addSql('ALTER TABLE project_evaluation_request DROP FOREIGN KEY FK_AED66974166D1F9C');
        $this->addSql('DROP TABLE project_evaluation_request');
        $this->addSql('ALTER TABLE user_project DROP FOREIGN KEY FK_77BECEE4C69DE5E5');
        $this->addSql('DROP INDEX IDX_77BECEE4C69DE5E5 ON user_project');
        $this->addSql('ALTER TABLE user_project DROP validated_by_id');
    }
}
