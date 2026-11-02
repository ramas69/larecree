<?php

declare(strict_types=1);

namespace App\Tests\Catalogue;

use App\Catalogue\Programme;
use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\Module;
use PHPUnit\Framework\TestCase;

final class ProgrammeTest extends TestCase
{
    /** @var list<list<Lesson>> */
    private array $lecons = [];

    private function formation(): Formation
    {
        $formation = new Formation();
        $modules = [
            ['Étape 1 · Cowork — Claude te libère la main dès aujourd\'hui', 'La première victoire, dans les 48 h. Tu sors avec : ta présentation d\'offre.', 2],
            ['Étape 2 · Skills — écrire une fois, pour toujours', 'Ce que tu refais chaque semaine. Tu sors avec : tes 2-3 skills.', 3],
            ['Étape 3 · Connecteurs', '<p>Texte écrit dans l\'admin.</p>', 1],
        ];
        foreach ($modules as $m => [$titre, $description, $nb]) {
            $module = (new Module())->setTitle($titre)->setSlug('etape-'.($m + 1))->setDescription($description)->setDisplayOrder($m + 1);
            $formation->addModule($module);
            for ($l = 0; $l < $nb; $l++) {
                $lesson = (new Lesson())->setTitle("Leçon $m.$l")->setSlug("m$m-l$l")->setDurationSeconds(600)->setDisplayOrder($l + 1)
                    ->setDescription("Ce qu'on voit.\n\n▶ À toi : liste tes 3 candidates.");
                $module->addLesson($lesson);
                $this->lecons[$m][$l] = $lesson;
            }
        }

        return $formation;
    }

    private function suivi(Enrollment $enrollment, Lesson $lesson): LessonProgress
    {
        $p = (new LessonProgress())->setLesson($lesson);
        $enrollment->addProgress($p);

        return $p;
    }

    public function testUneEleveALEtape2(): void
    {
        $formation = $this->formation();
        $enrollment = (new Enrollment())->setFormation($formation);
        $this->suivi($enrollment, $this->lecons[0][0])->markCompleted();
        $this->suivi($enrollment, $this->lecons[0][1])->markCompleted();
        $this->suivi($enrollment, $this->lecons[1][0])->markCompleted();
        $this->suivi($enrollment, $this->lecons[1][1])->recordWatch(240, 40);

        $programme = Programme::pour($formation, $enrollment);

        self::assertSame(['fait', 'encours', 'avenir'], array_map(fn ($e) => $e->statut, $programme->etapes));
        self::assertSame(1, $programme->ouverte);
        self::assertSame($this->lecons[1][1], $programme->reprise);
        self::assertSame(40, $programme->reprisePourcent);
        self::assertSame([6, 3, 50], [$programme->lecons, $programme->faites, $programme->pourcent()]);
        // 2 leçons pas commencées (1 200 s) + la leçon en cours, vue 240 s sur 600 (360 s)
        self::assertSame(1560, $programme->resteSecondes);

        $etape = $programme->etapes[1];
        self::assertSame(['fait', 'ici', ''], array_column($etape->lecons, 'etat'));
        self::assertSame('liste tes 3 candidates.', $etape->lecons[1]['atoi']);
        self::assertNull($etape->lecons[2]['atoi']);
    }

    public function testLeTitreEtLaDescriptionDuModuleSontDecoupes(): void
    {
        $etapes = Programme::pour($this->formation(), null)->etapes;

        self::assertSame('Cowork', $etapes[0]->nom);
        self::assertSame('Claude te libère la main dès aujourd\'hui. La première victoire, dans les 48 h.', $etapes[0]->promesse);
        self::assertSame('ta présentation d\'offre.', $etapes[0]->repars);
        self::assertSame('Écrire une fois, pour toujours. Ce que tu refais chaque semaine.', $etapes[1]->promesse);
        // sans promesse dans le titre, et une description en texte riche gardée telle quelle
        self::assertSame('Connecteurs', $etapes[2]->nom);
        self::assertNull($etapes[2]->promesse);
        self::assertNull($etapes[2]->repars);
        self::assertSame('<p>Texte écrit dans l\'admin.</p>', $etapes[2]->texte);
    }

    public function testSansInscriptionRienNEstCommence(): void
    {
        $programme = Programme::pour($this->formation(), null);

        self::assertNull($programme->reprise);
        self::assertSame(0, $programme->ouverte);
        self::assertSame(3600, $programme->resteSecondes);
        self::assertSame(['avenir', 'avenir', 'avenir'], array_map(fn ($e) => $e->statut, $programme->etapes));
    }

    public function testSansLeconCommenceeOnRepartDeLaPremiereAFaire(): void
    {
        $formation = $this->formation();
        $enrollment = (new Enrollment())->setFormation($formation);
        foreach ($this->lecons[0] as $lesson) {
            $this->suivi($enrollment, $lesson)->markCompleted();
        }

        $programme = Programme::pour($formation, $enrollment);

        self::assertSame($this->lecons[1][0], $programme->reprise);
        self::assertSame(0, $programme->reprisePourcent);
        self::assertSame(1, $programme->ouverte);
    }

    public function testToutEstFait(): void
    {
        $formation = $this->formation();
        $enrollment = (new Enrollment())->setFormation($formation);
        foreach (array_merge(...$this->lecons) as $lesson) {
            $this->suivi($enrollment, $lesson)->markCompleted();
        }

        $programme = Programme::pour($formation, $enrollment);

        self::assertNull($programme->reprise);
        self::assertSame(100, $programme->pourcent());
        self::assertSame(0, $programme->resteSecondes);
        self::assertSame(0, $programme->ouverte);
    }
}
