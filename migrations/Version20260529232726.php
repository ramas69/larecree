<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260529232726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE enrollment (id INT AUTO_INCREMENT NOT NULL, source VARCHAR(16) NOT NULL, stripe_session_id VARCHAR(200) DEFAULT NULL, stripe_payment_intent_id VARCHAR(200) DEFAULT NULL, amount_cents INT DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT DEFAULT NULL, formation_id INT DEFAULT NULL, INDEX IDX_DBDCD7E1A76ED395 (user_id), INDEX IDX_DBDCD7E15200282E (formation_id), UNIQUE INDEX UNIQ_enrollment_user_formation (user_id, formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E15200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E1A76ED395');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E15200282E');
        $this->addSql('DROP TABLE enrollment');
    }
}
