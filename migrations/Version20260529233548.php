<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260529233548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE lesson_progress (id INT AUTO_INCREMENT NOT NULL, watched_seconds INT NOT NULL, percent_watched INT NOT NULL, completed_at DATETIME DEFAULT NULL, last_watched_at DATETIME NOT NULL, created_at DATETIME NOT NULL, enrollment_id INT DEFAULT NULL, lesson_id INT DEFAULT NULL, INDEX IDX_6A46B85F8F7DB25B (enrollment_id), INDEX IDX_6A46B85FCDF80196 (lesson_id), UNIQUE INDEX UNIQ_lesson_progress_enrollment_lesson (enrollment_id, lesson_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE lesson_progress ADD CONSTRAINT FK_6A46B85F8F7DB25B FOREIGN KEY (enrollment_id) REFERENCES enrollment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lesson_progress ADD CONSTRAINT FK_6A46B85FCDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY FK_6A46B85F8F7DB25B');
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY FK_6A46B85FCDF80196');
        $this->addSql('DROP TABLE lesson_progress');
    }
}
