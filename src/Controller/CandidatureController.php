<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Candidature;
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
 * Le formulaire de candidature de larecreetech.com (candidature.html) envoie ici, à la place de Formspree :
 * la candidature est gardée dans la base, puis envoyée à hello@ (« Répondre » écrit au candidat).
 */
#[AsController]
final class CandidatureController
{
    private const HELLO = 'hello@larecreetech.com';
    /** Le site qui a le droit de lire la réponse (le formulaire l'envoie avec fetch). */
    private const ORIGINES = ['https://larecreetech.com', 'https://www.larecreetech.com'];
    // ponytail: un plafond global contre les rafales de robots ; une limite par adresse IP si un jour ça ne suffit plus
    private const PAR_HEURE = 20;

    #[Route('/candidature', name: 'app_candidature', methods: ['POST'])]
    public function __invoke(Request $requete, EntityManagerInterface $em, MailerInterface $mailer, LoggerInterface $logger): JsonResponse
    {
        $origine = (string) $requete->headers->get('Origin');
        $cors = \in_array($origine, self::ORIGINES, true) ? ['Access-Control-Allow-Origin' => $origine, 'Vary' => 'Origin'] : [];

        // le piège à robots (un champ caché que les humains ne remplissent pas) : on fait comme si tout allait bien
        if (trim((string) $requete->request->get('_gotcha', '')) !== '') {
            return new JsonResponse(['ok' => true], 201, $cors);
        }
        [$c, $erreurs] = Candidature::depuis($requete->request->all());
        if ($c === null) {
            return new JsonResponse(['erreurs' => $erreurs], 422, $cors);
        }
        $recentes = (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM candidature WHERE created_at > ?', [(new \DateTimeImmutable('-1 hour'))->format('Y-m-d H:i:s')]);
        if ($recentes >= self::PAR_HEURE) {
            return new JsonResponse(['erreurs' => ['general' => 'Trop de candidatures en ce moment, réessaie dans une heure.']], 429, $cors);
        }
        $em->persist($c);
        $em->flush();

        try {
            $mailer->send((new Email())
                ->from(new Address(self::HELLO, 'La Récrée Tech'))
                ->to(self::HELLO)
                ->replyTo($c->getEmail())
                ->subject($c->objet())
                ->text($c->texte()));
        } catch (\Throwable $e) {
            // la candidature est gardée (et visible dans la ville) : on ne la refuse pas pour un e-mail raté
            $logger->error('Candidature : e-mail à hello@ pas parti ({erreur})', ['erreur' => $e->getMessage()]);
        }

        return new JsonResponse(['ok' => true], 201, $cors);
    }
}
