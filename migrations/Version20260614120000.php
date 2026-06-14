<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Vidéo auto-hébergée : ajoute lesson.video_filename et rend vimeo_video_id optionnel
 * (une leçon a soit une vidéo locale, soit un ID Vimeo).
 */
final class Version20260614120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lesson: add video_filename (self-hosted video) + make vimeo_video_id nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lesson ADD video_filename VARCHAR(255) DEFAULT NULL, CHANGE vimeo_video_id vimeo_video_id VARCHAR(80) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE lesson SET vimeo_video_id = \'0\' WHERE vimeo_video_id IS NULL');
        $this->addSql('ALTER TABLE lesson DROP video_filename, CHANGE vimeo_video_id vimeo_video_id VARCHAR(80) NOT NULL');
    }
}
