<?php

declare(strict_types=1);

namespace App\Catalogue;

use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\Module;

/**
 * Le programme d'une formation tel que l'élève le voit (variante B « les onglets », choisie par Rama) :
 * une étape par module, où elle en est, et la leçon à reprendre.
 * Pur : ne lit que la formation et l'inscription déjà chargées.
 */
final class Programme
{
    /**
     * @param list<Etape> $etapes
     * @param int $ouverte l'étape montrée en premier : celle de la leçon à reprendre, sinon la première pas finie
     */
    public function __construct(
        public readonly array $etapes,
        public readonly int $lecons,
        public readonly int $faites,
        public readonly int $secondes,
        public readonly int $resteSecondes,
        public readonly ?Lesson $reprise,
        public readonly int $reprisePourcent,
        public readonly int $ouverte,
    ) {
    }

    public function pourcent(): int
    {
        return $this->lecons > 0 ? (int) round($this->faites * 100 / $this->lecons) : 0;
    }

    public static function pour(Formation $formation, ?Enrollment $enrollment): self
    {
        // Le suivi de chaque leçon, par objet (Doctrine ne charge qu'une fois chaque leçon).
        /** @var array<int, LessonProgress> $suivis */
        $suivis = [];
        $enCours = null;
        foreach ($enrollment?->getProgresses() ?? [] as $p) {
            if ($p->getLesson() === null) {
                continue;
            }
            $suivis[spl_object_id($p->getLesson())] = $p;
            if (!$p->isCompleted() && ($enCours === null || $p->getLastWatchedAt() > $enCours->getLastWatchedAt())) {
                $enCours = $p;
            }
        }
        $suivi = static fn (Lesson $l): ?LessonProgress => $suivis[spl_object_id($l)] ?? null;
        $fait = static fn (Lesson $l): bool => $suivi($l)?->isCompleted() ?? false;

        // Comme « Mon cartable » : la leçon commencée la plus récente, sinon la première pas terminée.
        $reprise = $enCours?->getLesson();
        if ($enrollment !== null && $reprise === null) {
            foreach ($formation->getModules() as $module) {
                foreach ($module->getLessons() as $lesson) {
                    if (!$fait($lesson)) {
                        $reprise = $lesson;
                        break 2;
                    }
                }
            }
        }

        $etapes = [];
        $lecons = $faites = $secondes = $reste = 0;
        foreach ($formation->getModules() as $module) {
            $liste = [];
            $faitesEtape = $secondesEtape = 0;
            foreach ($module->getLessons() as $lesson) {
                $estFaite = $fait($lesson);
                $ici = $lesson === $reprise;
                $liste[] = [
                    'lesson'   => $lesson,
                    'etat'     => $estFaite ? 'fait' : ($ici ? 'ici' : ''),
                    'pourcent' => $suivi($lesson)?->getPercentWatched() ?? 0,
                    'atoi'     => $ici ? self::aToi($lesson->getDescription()) : null,
                ];
                $faitesEtape += $estFaite ? 1 : 0;
                $secondesEtape += $lesson->getDurationSeconds();
                $reste += $estFaite ? 0 : max(0, $lesson->getDurationSeconds() - ($suivi($lesson)?->getWatchedSeconds() ?? 0));
            }

            $statut = $liste !== [] && $faitesEtape === count($liste) ? 'fait'
                : ($reprise !== null && $reprise->getModule() === $module ? 'encours' : 'avenir');
            [$nom, $promesse] = self::titre($module);
            [$intro, $repars, $texte] = self::description($module);

            $etapes[] = new Etape(
                numero: count($etapes) + 1,
                nom: $nom,
                promesse: trim(($promesse ?? '').' '.($intro ?? '')) ?: null,
                repars: $repars,
                texte: $texte,
                lecons: $liste,
                faites: $faitesEtape,
                secondes: $secondesEtape,
                statut: $statut,
            );
            $lecons += count($liste);
            $faites += $faitesEtape;
            $secondes += $secondesEtape;
        }

        $ouverte = 0;
        foreach ($etapes as $i => $etape) {
            if ($etape->statut === 'encours' || ($reprise === null && $etape->statut !== 'fait')) {
                $ouverte = $i;
                break;
            }
        }

        return new self(
            etapes: $etapes,
            lecons: $lecons,
            faites: $faites,
            secondes: $secondes,
            resteSecondes: $enrollment !== null ? $reste : $secondes,
            reprise: $reprise,
            reprisePourcent: $reprise !== null ? ($suivi($reprise)?->getPercentWatched() ?? 0) : 0,
            ouverte: $ouverte,
        );
    }

    /**
     * « Étape 1 · Cowork — Claude te libère la main » → ['Cowork', 'Claude te libère la main.']
     * Le numéro est retiré : la page le montre déjà une fois.
     *
     * @return array{0: string, 1: ?string}
     */
    private static function titre(Module $module): array
    {
        $titre = (string) preg_replace('/^\s*(?:étape|cours|module)\s*\d+\s*[·:.–—-]\s*/iu', '', (string) $module->getTitle());
        $morceaux = preg_split('/\s+[—–]\s+/u', $titre, 2) ?: [$titre];

        return [trim($morceaux[0]), isset($morceaux[1]) ? self::phrase($morceaux[1]) : null];
    }

    /**
     * « La première victoire. Tu sors avec : ton offre » → ['La première victoire.', 'ton offre', null].
     * Une description en texte riche (écrite dans l'admin) reste telle quelle, en troisième.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private static function description(Module $module): array
    {
        $texte = trim((string) $module->getDescription());
        if ($texte === '') {
            return [null, null, null];
        }
        if (str_contains($texte, '<')) {
            return [null, null, $texte];
        }
        if (preg_match('/^(.*?)\s*Tu (?:sors|repars) avec\s*:\s*(.+)$/su', $texte, $m) === 1) {
            return [trim($m[1]) !== '' ? self::phrase($m[1]) : null, trim($m[2]), null];
        }

        return [self::phrase($texte), null, null];
    }

    /** « ▶ À toi : liste tes 3 candidates. » dans la description d'une leçon. */
    private static function aToi(?string $description): ?string
    {
        $texte = html_entity_decode(strip_tags((string) $description), ENT_QUOTES | ENT_HTML5);

        return preg_match('/À toi\s*:\s*(.+)/u', $texte, $m) === 1 ? trim($m[1]) : null;
    }

    /** Majuscule au début, point à la fin. */
    private static function phrase(string $texte): string
    {
        $texte = trim($texte);
        $texte = mb_strtoupper(mb_substr($texte, 0, 1)).mb_substr($texte, 1);

        return preg_match('/[.!?…]$/u', $texte) === 1 ? $texte : $texte.'.';
    }
}
