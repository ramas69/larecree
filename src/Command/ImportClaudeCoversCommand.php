<?php

declare(strict_types=1);

namespace App\Command;

use App\Data\ClaudeProgram;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Les couvertures des étapes et des leçons de la formation Claude (identité « école », novembre 2026).
 * Les sources sont versionnées dans public/images/etapes/<slug du module>.jpg et public/images/lecons/<slug de la leçon>.jpg,
 * faites depuis le modèle ~/Desktop/Perso/videos/formation-claude/couvertures/.
 * Les anciennes images d'ouverture des modules (charte rose) ne sont plus importées.
 */
#[AsCommand(
    name: 'app:import-claude-covers',
    description: 'Copie les couvertures des étapes (public/images/etapes) et des leçons (public/images/lecons) de la formation Claude vers public/uploads et renseigne Module.coverImage et Lesson.coverImage.',
)]
final class ImportClaudeCoversCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FormationRepository $formations,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $formation = $this->formations->findBySlug(ClaudeProgram::FORMATION_SLUG);
        if ($formation === null) {
            $io->error('Formation '.ClaudeProgram::FORMATION_SLUG.' introuvable.');
            return Command::FAILURE;
        }

        $done = 0;
        foreach ($formation->getModules() as $module) {
            // l'étape : public/images/etapes/<slug du module>.jpg → public/uploads/modules/
            $filename = $this->copier('etapes/'.$module->getSlug().'.jpg', 'modules', $formation->getSlug().'-'.$module->getSlug().'.jpg', $io);
            if ($filename !== null) {
                $module->setCoverImage($filename);
                $done++;
            }
            // ses leçons : public/images/lecons/<slug de la leçon>.jpg → public/uploads/lessons/
            foreach ($module->getLessons() as $lesson) {
                $filename = $this->copier('lecons/'.$lesson->getSlug().'.jpg', 'lessons', $formation->getSlug().'-'.$lesson->getSlug().'.jpg', $io);
                if ($filename !== null) {
                    $lesson->setCoverImage($filename);
                    $done++;
                }
            }
        }

        $this->em->flush();
        $io->success($done.' couverture(s) importée(s).');

        return Command::SUCCESS;
    }

    /** Copie public/images/<source> vers public/uploads/<dossier>/<nom> ; null si pas de source (rien à faire) ou si la copie échoue. */
    private function copier(string $source, string $dossier, string $nom, SymfonyStyle $io): ?string
    {
        $src = $this->projectDir.'/public/images/'.$source;
        if (!is_file($src)) {
            return null;
        }
        $destDir = $this->projectDir.'/public/uploads/'.$dossier;
        if (!is_dir($destDir) && !mkdir($destDir, 0775, true) && !is_dir($destDir)) {
            $io->warning('Impossible de créer '.$destDir);
            return null;
        }
        if (!copy($src, $destDir.'/'.$nom)) {
            $io->warning($source.' : copie échouée, sautée.');
            return null;
        }
        $io->writeln('  '.$source.' → /uploads/'.$dossier.'/'.$nom);

        return $nom;
    }
}
