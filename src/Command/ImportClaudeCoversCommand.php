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

#[AsCommand(
    name: 'app:import-claude-covers',
    description: 'Copie les visuels d\'ouverture des modules Claude (public/images/modules) vers public/uploads/modules et renseigne Module.coverImage.',
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
            $io->error('Formation claude-2026 introuvable.');
            return Command::FAILURE;
        }

        $srcDir  = $this->projectDir.'/public/images/modules';
        $destDir = $this->projectDir.'/public/uploads/modules';
        if (!is_dir($destDir) && !mkdir($destDir, 0775, true) && !is_dir($destDir)) {
            $io->error('Impossible de créer '.$destDir);
            return Command::FAILURE;
        }

        $done = 0;
        foreach ($formation->getModules() as $module) {
            $n = $module->getDisplayOrder();
            $src = sprintf('%s/module-%02d-ouverture.png', $srcDir, $n);
            if (!is_file($src)) {
                $io->warning(sprintf('M%02d : source absente (%s) — skip.', $n, $src));
                continue;
            }
            $filename = $module->getSlug().'.png';
            $dest = $destDir.'/'.$filename;
            if (!copy($src, $dest)) {
                $io->warning(sprintf('M%02d : copie échouée — skip.', $n));
                continue;
            }
            $module->setCoverImage($filename);
            $io->writeln(sprintf('  M%02d %s → /uploads/modules/%s', $n, $module->getTitle(), $filename));
            $done++;
        }

        $this->em->flush();
        $io->success($done.' couvertures importées.');

        return Command::SUCCESS;
    }
}
