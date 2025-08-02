<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802104202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agenda DROP users');
        $this->addSql('ALTER TABLE event ADD agenda_id INT NOT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7EA67784A FOREIGN KEY (agenda_id) REFERENCES agenda (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7EA67784A ON event (agenda_id)');
        $this->addSql('ALTER TABLE user ADD agenda_id INT DEFAULT NULL, ADD confirmation_token VARCHAR(64) DEFAULT NULL, ADD is_active TINYINT(1) NOT NULL, CHANGE password password VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649EA67784A FOREIGN KEY (agenda_id) REFERENCES agenda (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C05FB297 ON user (confirmation_token)');
        $this->addSql('CREATE INDEX IDX_8D93D649EA67784A ON user (agenda_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7EA67784A');
        $this->addSql('DROP INDEX IDX_3BAE0AA7EA67784A ON event');
        $this->addSql('ALTER TABLE event DROP agenda_id');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649EA67784A');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON `user`');
        $this->addSql('DROP INDEX UNIQ_8D93D649C05FB297 ON `user`');
        $this->addSql('DROP INDEX IDX_8D93D649EA67784A ON `user`');
        $this->addSql('ALTER TABLE `user` DROP agenda_id, DROP confirmation_token, DROP is_active, CHANGE password password VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE agenda ADD users LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }
}
