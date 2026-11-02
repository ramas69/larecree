<?php

declare(strict_types=1);

namespace App\Catalogue;

use App\Entity\Lesson;

/**
 * Une étape du programme (un module) : un seul numéro, un nom court, sa promesse
 * et ce que l'élève en emporte, lus dans le titre et la description du module.
 */
final class Etape
{
    /**
     * @param list<array{lesson: Lesson, etat: string, pourcent: int, atoi: ?string}> $lecons
     *        etat : 'fait', 'ici' (la leçon à reprendre) ou ''
     * @param string $statut 'fait', 'encours' ou 'avenir'
     */
    public function __construct(
        public readonly int $numero,
        public readonly string $nom,
        public readonly ?string $promesse,
        public readonly ?string $repars,
        public readonly ?string $texte,
        public readonly array $lecons,
        public readonly int $faites,
        public readonly int $secondes,
        public readonly string $statut,
    ) {
    }

    public function pourcent(): int
    {
        return $this->lecons === [] ? 0 : (int) round($this->faites * 100 / count($this->lecons));
    }
}
