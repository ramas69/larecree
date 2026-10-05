<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Data\ClaudeProgram;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Re-seed Formation Claude 2026 → programme V3 fil rouge (5 étapes · 30 vidéos · ~5 h 20).
 *
 * Remplace le V2 (10 modules · 64 leçons) par le V3 défini dans ClaudeProgram.
 *
 * ⚠️ Supprime les modules existants de claude-2026 → cascade FK efface lessons,
 * resources, lesson_progress liés. OK au 5 oct. 2026 : aucune vidéo ni progression
 * réelle en prod. Les vidéos seront uploadées leçon par leçon dans l'admin.
 *
 * Idempotence : skip si les 5 étapes V3 (par slug) sont déjà toutes présentes.
 */
final class Version20261005150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Re-seed Claude 2026 program V3 — 5 steps, 30 lessons (data only).';
    }

    public function up(Schema $schema): void
    {
        $formationId = $this->connection->fetchOne(
            'SELECT id FROM formation WHERE slug = :slug',
            ['slug' => ClaudeProgram::FORMATION_SLUG],
        );
        $this->skipIf($formationId === false, 'Formation claude-2026 absente.');
        $formationId = (int) $formationId;

        $slugs = array_column(ClaudeProgram::modules(), 'slug');
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $existing = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM module WHERE formation_id = ? AND slug IN ($placeholders)",
            array_merge([$formationId], $slugs),
        );
        $this->skipIf($existing === count($slugs), 'Claude 2026 already at V3.');

        $this->connection->update('formation', [
            'subtitle'    => ClaudeProgram::FORMATION_SUBTITLE,
            'description' => ClaudeProgram::FORMATION_DESCRIPTION,
        ], ['id' => $formationId]);

        // Purge des anciens modules (cascade lessons/resources/lesson_progress via FK).
        $this->connection->executeStatement(
            'DELETE FROM module WHERE formation_id = :fid',
            ['fid' => $formationId],
        );

        $now = '2026-10-05 15:00:00';

        foreach (ClaudeProgram::modules() as $mIdx => $module) {
            $moduleNumber = $mIdx + 1;
            $this->connection->insert('module', [
                'formation_id'  => $formationId,
                'slug'          => $module['slug'],
                'title'         => $module['title'],
                'description'   => $module['description'],
                'display_order' => $moduleNumber,
                'created_at'    => $now,
            ]);
            $moduleId = (int) $this->connection->lastInsertId();

            foreach ($module['lessons'] as $lIdx => [$title, $duration, $description]) {
                $lessonNumber = $lIdx + 1;
                $this->connection->insert('lesson', [
                    'module_id'        => $moduleId,
                    'slug'             => 'm'.$moduleNumber.'-l'.$lessonNumber,
                    'title'            => $title,
                    'description'      => $description,
                    'duration_seconds' => $duration,
                    'display_order'    => $lessonNumber,
                    'created_at'       => $now,
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Pas de rollback du contenu (data migration). No-op.
    }
}
