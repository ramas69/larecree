<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Data\ClaudeProgram;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Re-seed Formation Claude 2026 → programme V2 (10 modules · 64 leçons).
 *
 * Remplace le contenu V1 (8 modules · 52 leçons) par le V2 défini dans ClaudeProgram.
 *
 * ⚠️ Supprime les modules existants de claude-2026 → cascade FK efface lessons,
 * resources, lesson_progress liés. À lancer AVANT que des étudiants aient une
 * progression réelle à conserver (au lancement, OK). La progression d'un éventuel
 * étudiant existant sur la V1 sera réinitialisée.
 *
 * Idempotence : skip si les 10 modules V2 (par slug) sont déjà tous présents.
 */
final class Version20260601140049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Re-seed Claude 2026 program V2 — 10 modules, 64 lessons (data only).';
    }

    public function up(Schema $schema): void
    {
        $formationId = $this->connection->fetchOne(
            'SELECT id FROM formation WHERE slug = :slug',
            ['slug' => ClaudeProgram::FORMATION_SLUG],
        );

        // Si la formation n'existe pas encore (DB neuve), la créer.
        if ($formationId === false) {
            $this->connection->insert('formation', [
                'slug'          => ClaudeProgram::FORMATION_SLUG,
                'title'         => ClaudeProgram::FORMATION_TITLE,
                'subtitle'      => ClaudeProgram::FORMATION_SUBTITLE,
                'description'   => ClaudeProgram::FORMATION_DESCRIPTION,
                'price_cents'   => ClaudeProgram::FORMATION_PRICE_CENTS,
                'currency'      => 'EUR',
                'published'     => 1,
                'coming_soon'   => 0,
                'display_order' => 1,
                'created_at'    => '2026-06-01 14:00:00',
            ]);
            $formationId = (int) $this->connection->lastInsertId();
        } else {
            $formationId = (int) $formationId;
        }

        // Idempotence : déjà en V2 ?
        $v2Slugs = array_column(ClaudeProgram::modules(), 'slug');
        $placeholders = implode(',', array_fill(0, count($v2Slugs), '?'));
        $existingV2 = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM module WHERE formation_id = ? AND slug IN ($placeholders)",
            array_merge([$formationId], $v2Slugs),
        );
        $this->skipIf($existingV2 === count($v2Slugs), 'Claude 2026 already at V2.');

        // Met à jour les métadonnées formation (sous-titre / description V2).
        $this->connection->update('formation', [
            'subtitle'    => ClaudeProgram::FORMATION_SUBTITLE,
            'description' => ClaudeProgram::FORMATION_DESCRIPTION,
        ], ['id' => $formationId]);

        // Purge des anciens modules (cascade lessons/resources/lesson_progress via FK).
        $this->connection->executeStatement(
            'DELETE FROM module WHERE formation_id = :fid',
            ['fid' => $formationId],
        );

        $now = '2026-06-01 14:00:00';

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
                    'vimeo_video_id'   => ClaudeProgram::VIMEO_PREFIX.$moduleNumber.$lessonNumber,
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
