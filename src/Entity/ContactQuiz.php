<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Une personne qui a fait le quiz de larecreetech.com (quizz/) et laissé son e-mail pour recevoir son plan d'action, avec son accord.
 * Une seule fois par adresse : refaire le quiz ne la garde pas une deuxième fois.
 */
#[ORM\Entity]
#[ORM\Table(name: 'contact_quiz')]
#[ORM\UniqueConstraint(name: 'UNIQ_contact_quiz_email', columns: ['email'])]
class ContactQuiz
{
    /** Les profils du quiz (les mêmes que dans quizz/index.html). */
    public const PROFILS = ['Le Collectionneur', 'Le Prudent', 'Le Bricoleur', 'Le Pilote'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    private function __construct(
        #[ORM\Column(length: 180)] private string $email,
        #[ORM\Column(length: 40, nullable: true)] private ?string $profil,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Le contact tiré des champs du quiz, ou les erreurs (champ => message). Un profil inconnu n'empêche rien : il est seulement oublié.
     *
     * @param array<string, mixed> $f
     *
     * @return array{0: ?self, 1: array<string, string>}
     */
    public static function depuis(array $f): array
    {
        $email = \is_string($f['email'] ?? null) ? trim($f['email']) : '';
        $erreurs = array_filter([
            'email' => filter_var($email, \FILTER_VALIDATE_EMAIL) && mb_strlen($email) <= 180 ? null : 'Un e-mail valable.',
            'consentement' => ($f['consentement'] ?? '') !== '' ? null : 'Ton accord pour recevoir ton plan par e-mail.',
        ]);
        if ($erreurs) {
            return [null, $erreurs];
        }
        $profil = \in_array($f['profil'] ?? null, self::PROFILS, true) ? $f['profil'] : null;

        return [new self(mb_strtolower($email), $profil), []];
    }

    public function getEmail(): string { return $this->email; }

    public function objet(): string
    {
        return '🌸 Quiz — '.($this->profil ?? 'profil inconnu');
    }

    public function texte(): string
    {
        return "Quelqu'un a fait le quiz de La Récrée Tech et a demandé son plan d'action.\n\nEmail : {$this->email}\nProfil : ".($this->profil ?? '—')
            ."\n\n—\nAccord donné pour recevoir le plan et les conseils de La Récrée Tech par e-mail.";
    }
}
