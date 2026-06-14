<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Retrait complet de Vimeo : supprime lesson.vimeo_video_id et formation.vimeo_folder_id.
 * Les vidéos sont désormais 100 % auto-hébergées.
 */
final class Version20260614130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop Vimeo columns (lesson.vimeo_video_id, formation.vimeo_folder_id)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lesson DROP vimeo_video_id');
        $this->addSql('ALTER TABLE formation DROP vimeo_folder_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lesson ADD vimeo_video_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE formation ADD vimeo_folder_id VARCHAR(80) DEFAULT NULL');
    }
}
