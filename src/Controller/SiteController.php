<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\ContactQuiz;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Les formulaires de larecreetech.com envoient ici, à la place de Formspree : la candidature à la cohorte (candidature.html)
 * et l'e-mail laissé à la fin du quiz (quizz/). Chacun est gardé dans la base, puis envoyé à hello@ (« Répondre » écrit à la personne).
 */
#[AsController]
final class SiteController
{
    private const HELLO = 'hello@larecreetech.com';
    /** Le site qui a le droit de lire la réponse (les formulaires envoient avec fetch). */
    private const ORIGINES = ['https://larecreetech.com', 'https://www.larecreetech.com'];

    public function __construct(private EntityManagerInterface $em, private MailerInterface $mailer, private LoggerInterface $logger)
    {
    }

    #[Route('/candidature', name: 'app_candidature', methods: ['POST'])]
    public function candidature(Request $requete): JsonResponse
    {
        if ($robot = $this->robot($requete)) {
            return $robot;
        }
        [$c, $erreurs] = Candidature::depuis($requete->request->all());
        if ($c === null) {
            return $this->reponse($requete, ['erreurs' => $erreurs], 422);
        }
        // ponytail: un plafond global contre les rafales de robots ; une limite par adresse IP si un jour ça ne suffit plus
        if ($this->recents('candidature') >= 20) {
            return $this->reponse($requete, ['erreurs' => ['general' => 'Trop de candidatures en ce moment, réessaie dans une heure.']], 429);
        }
        $this->em->persist($c);
        $this->em->flush();
        $this->envoyer($c->getEmail(), $c->objet(), $c->texte());

        return $this->reponse($requete, ['ok' => true], 201);
    }

    #[Route('/quiz', name: 'app_quiz', methods: ['POST'])]
    public function quiz(Request $requete): JsonResponse
    {
        if ($robot = $this->robot($requete)) {
            return $robot;
        }
        [$c, $erreurs] = ContactQuiz::depuis($requete->request->all());
        if ($c === null) {
            return $this->reponse($requete, ['erreurs' => $erreurs], 422);
        }
        // déjà là (elle refait le quiz), ou trop de monde d'un coup : elle a son plan quand même, sans nouvel e-mail à hello@
        $deja = $this->em->getRepository(ContactQuiz::class)->findOneBy(['email' => $c->getEmail()]) !== null;
        if (!$deja && $this->recents('contact_quiz') < 100) {
            $this->em->persist($c);
            $this->em->flush();
            $this->envoyer($c->getEmail(), $c->objet(), $c->texte());
        }

        return $this->reponse($requete, ['ok' => true], 201);
    }

    /** Le piège à robots (un champ caché que les humains ne remplissent pas) : on fait comme si tout allait bien. */
    private function robot(Request $requete): ?JsonResponse
    {
        return trim((string) $requete->request->get('_gotcha', '')) !== '' ? $this->reponse($requete, ['ok' => true], 201) : null;
    }

    private function recents(string $table): int
    {
        return (int) $this->em->getConnection()->fetchOne("SELECT COUNT(*) FROM $table WHERE created_at > ?", [(new \DateTimeImmutable('-1 hour'))->format('Y-m-d H:i:s')]);
    }

    /** L'e-mail à hello@ ; s'il ne part pas, la personne est gardée quand même (et visible dans la ville de Rama). */
    private function envoyer(string $repondreA, string $objet, string $texte): void
    {
        try {
            $this->mailer->send((new Email())->from(new Address(self::HELLO, 'La Récrée Tech'))->to(self::HELLO)->replyTo($repondreA)->subject($objet)->text($texte));
        } catch (\Throwable $e) {
            $this->logger->error('Formulaire du site : e-mail à hello@ pas parti ({erreur})', ['erreur' => $e->getMessage()]);
        }
    }

    private function reponse(Request $requete, array $donnees, int $code): JsonResponse
    {
        $origine = (string) $requete->headers->get('Origin');

        return new JsonResponse($donnees, $code, \in_array($origine, self::ORIGINES, true) ? ['Access-Control-Allow-Origin' => $origine, 'Vary' => 'Origin'] : []);
    }
}
