<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CommunityController extends AbstractController
{
    private const DISCORD_INVITE = 'https://discord.gg/Pb8cuuuAJ';

    #[Route('/communaute', name: 'app_community')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): Response
    {
        return $this->render('community/index.html.twig', [
            'discordInvite' => self::DISCORD_INVITE,
            'channels'      => $this->channels(),
            'steps'         => $this->steps(),
            'rules'         => $this->rules(),
            'wins'          => $this->wins(),
        ]);
    }

    /**
     * @return list<array{name: string, desc: string}>
     */
    private function channels(): array
    {
        return [
            ['name' => '#wins', 'desc' => 'Tu shippes un truc ? Tu le poses ici. On célèbre, même les petites victoires.'],
            ['name' => '#questions', 'desc' => 'Bloqué·e sur un bug, une commande, une idée ? Demande. Quelqu\'un est passé par là.'],
            ['name' => '#vos-projets', 'desc' => 'Montre ce que tu construis. Feedback honnête, jamais gratuit.'],
            ['name' => '#ressources', 'desc' => 'Les liens, prompts et outils qui valent le coup. Partagés, triés, gardés.'],
        ];
    }

    /**
     * @return list<array{num: string, title: string, desc: string}>
     */
    private function steps(): array
    {
        return [
            ['num' => '01', 'title' => 'Rejoins', 'desc' => 'Un clic sur le bouton, tu acceptes l\'invitation, tu es dedans. Gratuit, à vie.'],
            ['num' => '02', 'title' => 'Présente-toi', 'desc' => 'Passe par #presentations : qui tu es, ce que tu veux construire. On retient les prénoms.'],
            ['num' => '03', 'title' => 'Poste ton premier win', 'desc' => 'Aussi petit soit-il. Le premier "ça marche" lance la machine — la tienne et celle des autres.'],
        ];
    }

    /**
     * @return list<string>
     */
    private function rules(): array
    {
        return [
            'Bienveillance par défaut. On est tous passés par le moment où on ne pigeait rien.',
            'Pas de question bête. La seule erreur, c\'est de rester bloqué·e en silence.',
            'Tu reçois, tu redonnes. Réponds quand tu peux, partage ce que tu apprends.',
            'Zéro spam, zéro démarchage. C\'est un atelier, pas une place de marché.',
            'Ce qui se dit ici reste ici. On parle vrai parce qu\'on se fait confiance.',
        ];
    }

    /**
     * @return list<array{quote: string, author: string, project: string}>
     */
    private function wins(): array
    {
        return [
            ['quote' => 'Première app en prod en 3 semaines. Mes clients la utilisent déjà. Je ne savais pas écrire une ligne de code en janvier.', 'author' => 'Naïma', 'project' => 'Outil de devis pour son cabinet'],
            ['quote' => 'J\'ai posté un bug à 23h, trois personnes m\'avaient débloqué avant minuit. C\'est ça, la différence.', 'author' => 'Karim', 'project' => 'SaaS de réservation'],
            ['quote' => 'Le Discord m\'a fait tenir quand j\'allais lâcher. Aujourd\'hui c\'est moi qui réponds aux nouveaux.', 'author' => 'Lucie', 'project' => 'Automatisations métier'],
        ];
    }
}
