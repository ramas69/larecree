<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le catalogue (demande de Rama, 3 novembre 2026) : la 2e formation n'est pas Manus mais ChatGPT,
 * et Hermes Agent arrive en 3e. Les deux restent « Bientôt » (pas de page, pas de paiement).
 * Idempotent : chaque changement est sauté s'il est déjà fait.
 */
final class Version20261103090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Catalogue : Manus devient ChatGPT, Hermes Agent ajouté (tous deux « Bientôt ») — data only, idempotent.';
    }

    public function up(Schema $schema): void
    {
        $this->connection->update('formation', [
            'slug'        => 'chatgpt-2026',
            'title'       => 'Formation ChatGPT',
            'subtitle'    => 'ChatGPT, piloté pour ton business.',
            'description' => 'Bientôt disponible.',
        ], ['slug' => 'manus-2026']);

        if ($this->connection->fetchOne('SELECT id FROM formation WHERE slug = :slug', ['slug' => 'hermes-agent-2026']) === false) {
            $this->connection->insert('formation', [
                'slug'          => 'hermes-agent-2026',
                'title'         => 'Formation Hermes Agent',
                'subtitle'      => 'Ton agent IA à toi, qui travaille même quand tu n\'es pas là.',
                'description'   => 'Bientôt disponible.',
                'price_cents'   => 34700,
                'currency'      => 'EUR',
                'published'     => 1,
                'coming_soon'   => 1,
                'display_order' => 3,
                'created_at'    => '2026-11-03 09:00:00',
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->delete('formation', ['slug' => 'hermes-agent-2026']);
        $this->connection->update('formation', [
            'slug'        => 'manus-2026',
            'title'       => 'Formation Manus 2026',
            'subtitle'    => 'L\'agent IA qui exécute pendant que tu fais autre chose.',
            'description' => 'Découvre Manus, le co-pilote autonome qui transforme tes intentions en livrables. Bientôt disponible.',
        ], ['slug' => 'chatgpt-2026']);
    }
}
