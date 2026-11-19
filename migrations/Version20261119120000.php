<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Une couverture par leçon (demande de Rama, 19 novembre 2026) : l'aperçu de la vidéo, et l'image de la leçon tant que la vidéo n'est pas enregistrée.
 */
final class Version20261119120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Leçon : la couverture (lesson.cover_image).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lesson ADD cover_image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lesson DROP cover_image');
    }
}
