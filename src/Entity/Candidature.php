<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une candidature à la cohorte, envoyée par le formulaire de larecreetech.com (candidature.html).
 * Les choix sont gardés en clair (« Freelance », pas « freelance ») : c'est ce que lisent l'e-mail à hello@ et la ville de Rama.
 */
#[ORM\Entity]
#[ORM\Table(name: 'candidature')]
class Candidature
{
    /** Les choix du formulaire : la valeur envoyée => le libellé gardé (les mêmes que dans candidature.html). */
    public const CHOIX = [
        'situation' => ['solopreneur' => 'Solopreneur·e', 'freelance' => 'Freelance', 'createur' => 'Créateur·rice', 'transition' => 'Salarié·e en transition', 'autre' => 'Autre'],
        'claude_level' => ['never' => 'Jamais vraiment essayé', 'tested' => 'Quelques tests', 'regular' => 'Utilisation régulière', 'builder' => 'A déjà construit avec'],
        'project_idea' => ['clear' => 'Oui, claire', 'vague' => 'Oui, encore floue', 'none' => 'Pas encore — compte sur la phase 1'],
        'availability' => ['systematic' => 'Oui, systématiquement', 'usually' => 'Oui, sauf imprévu', 'sometimes' => 'Parfois — replay', 'no' => 'Pas vraiment, mais veut candidater'],
        'source' => ['linkedin' => 'LinkedIn', 'twitter' => 'Twitter / X', 'recommendation' => 'Recommandation', 'newsletter' => 'Newsletter', 'search' => 'Recherche Google', 'other' => 'Autre'],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    private function __construct(
        #[ORM\Column(length: 100)] private string $prenom,
        #[ORM\Column(length: 100)] private string $nom,
        #[ORM\Column(length: 180)] private string $email,
        #[ORM\Column(length: 40, nullable: true)] private ?string $telephone,
        #[ORM\Column(length: 40)] private string $situation,
        #[ORM\Column(length: 500, nullable: true)] private ?string $lien,
        #[ORM\Column(length: 40)] private string $niveauClaude,
        #[ORM\Column(length: 60)] private string $idee,
        #[ORM\Column(type: Types::TEXT)] private string $projet,
        #[ORM\Column(length: 60)] private string $disponibilite,
        #[ORM\Column(length: 40)] private string $source,
        #[ORM\Column(type: Types::SMALLINT, nullable: true)] private ?int $cohorte,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * La candidature tirée des champs du formulaire, ou les erreurs (champ => message) si elle n'est pas valable.
     *
     * @param array<string, mixed> $f
     *
     * @return array{0: ?self, 1: array<string, string>}
     */
    public static function depuis(array $f): array
    {
        // une seule ligne par champ (ni retour à la ligne dans l'objet de l'e-mail, ni tableau envoyé par un robot) ; le projet garde ses lignes
        $t = static fn (string $n) => \is_string($f[$n] ?? null) ? trim((string) preg_replace('/\s+/u', ' ', $f[$n])) : '';
        $projet = \is_string($f['project_text'] ?? null) ? trim(str_replace("\r", '', $f['project_text'])) : '';
        $choix = static fn (string $n) => self::CHOIX[$n][$t($n)] ?? null;
        $lien = $t('link');
        $erreurs = array_filter([
            'prenom' => $t('prenom') !== '' && mb_strlen($t('prenom')) <= 100 ? null : 'Ton prénom.',
            'nom' => $t('nom') !== '' && mb_strlen($t('nom')) <= 100 ? null : 'Ton nom.',
            'email' => filter_var($t('email'), \FILTER_VALIDATE_EMAIL) && mb_strlen($t('email')) <= 180 ? null : 'Un e-mail valable.',
            'telephone' => mb_strlen($t('telephone')) <= 40 ? null : 'Un numéro de téléphone.',
            'link' => $lien === '' || (mb_strlen($lien) <= 500 && preg_match('~^https?://~i', $lien)) ? null : 'Un lien qui commence par https://.',
            'project_text' => $projet !== '' && mb_strlen($projet) <= 5000 ? null : 'Quelques lignes sur ton projet (5 000 caractères au plus).',
            'rgpd' => $t('rgpd') !== '' ? null : 'Ton accord pour le traitement de tes données.',
            ...array_map(static fn (string $n) => $choix($n) === null ? 'Un des choix proposés.' : null, array_combine(array_keys(self::CHOIX), array_keys(self::CHOIX))),
        ]);
        if ($erreurs) {
            return [null, $erreurs];
        }
        $cohorte = filter_var($t('cohorte'), \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);

        return [new self(
            $t('prenom'), $t('nom'), $t('email'), $t('telephone') ?: null, $choix('situation'), $lien ?: null, $choix('claude_level'),
            $choix('project_idea'), $projet, $choix('availability'), $choix('source'), $cohorte === false ? null : $cohorte,
        ), []];
    }

    public function getEmail(): string { return $this->email; }

    public function objet(): string
    {
        return \sprintf('Candidature%s — %s %s', $this->cohorte ? \sprintf(' cohorte %02d', $this->cohorte) : '', $this->prenom, $this->nom);
    }

    /** Le récapitulatif envoyé à hello@ (le même que celui qu'envoyait Formspree). */
    public function texte(): string
    {
        return implode("\n", [
            'Candidature'.($this->cohorte ? \sprintf(' cohorte %02d', $this->cohorte) : '').' de La Récrée Tech.',
            '',
            '— QUI JE SUIS —',
            'Prénom : '.$this->prenom,
            'Nom : '.$this->nom,
            'Email : '.$this->email,
            'Téléphone : '.($this->telephone ?? '—'),
            '',
            '— SITUATION PRO —',
            'Profil : '.$this->situation,
            'Lien : '.($this->lien ?? '—'),
            '',
            '— AVEC CLAUDE —',
            'Niveau actuel : '.$this->niveauClaude,
            'Idée de projet : '.$this->idee,
            '',
            '— PROJET —',
            $this->projet,
            '',
            '— DISPONIBILITÉ —',
            'Dimanche 9h30-11h30 : '.$this->disponibilite,
            '',
            '— PROVENANCE —',
            'Source : '.$this->source,
            '',
            '—',
            'Données traitées avec consentement explicite (RGPD).',
        ]);
    }
}
