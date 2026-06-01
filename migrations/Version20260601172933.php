<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seed la Formation Manus 2026 en « Bientôt » (coming soon) si absente.
 * Idempotent : skip si le slug manus-2026 existe déjà.
 */
final class Version20260601172933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed Formation Manus 2026 (coming soon) — data only, idempotent.';
    }

    public function up(Schema $schema): void
    {
        $exists = $this->connection->fetchOne(
            'SELECT id FROM formation WHERE slug = :slug',
            ['slug' => 'manus-2026'],
        );
        $this->skipIf($exists !== false, 'Manus 2026 already seeded.');

        $this->connection->insert('formation', [
            'slug'          => 'manus-2026',
            'title'         => 'Formation Manus 2026',
            'subtitle'      => 'L\'agent IA qui exécute pendant que tu fais autre chose.',
            'description'   => 'Découvre Manus, le co-pilote autonome qui transforme tes intentions en livrables. Bientôt disponible.',
            'price_cents'   => 34700,
            'currency'      => 'EUR',
            'published'     => 1,
            'coming_soon'   => 1,
            'display_order' => 2,
            'created_at'    => '2026-06-01 17:29:00',
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->connection->delete('formation', ['slug' => 'manus-2026']);
    }
}
