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
        ]);
    }

    /**
     * @return list<array{name: string, desc: string}>
     */
    private function channels(): array
    {
        return [
            ['name' => '#presentations', 'desc' => 'Pour dire bonjour à la classe, le jour où tu arrives.'],
            ['name' => '#wins', 'desc' => 'Un devoir rendu, une chose qui marche ? Tu la poses ici. On fête aussi les petites.'],
            ['name' => '#questions', 'desc' => 'Tu bloques sur une commande, un réglage, une idée ? Demande : quelqu\'un de la classe est passé par là.'],
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
            ['num' => '01', 'title' => 'Rejoins', 'desc' => 'Un clic sur le bouton, tu acceptes l\'invitation, tu es dans la classe.'],
            ['num' => '02', 'title' => 'Présente-toi', 'desc' => 'Passe par #presentations : qui tu es, ce que tu veux construire. On retient les prénoms.'],
            ['num' => '03', 'title' => 'Montre ton premier devoir', 'desc' => 'Dans #wins, même s\'il est petit. Ton premier « ça marche » donne envie aux autres de rendre le leur.'],
        ];
    }

    /**
     * @return list<string>
     */
    private function rules(): array
    {
        return [
            'Bienveillance d\'abord. Tout le monde est passé par le moment où rien ne marchait.',
            'Pas de question bête. La seule erreur, c\'est de bloquer en silence.',
            'Tu reçois, tu redonnes. Réponds quand tu peux, partage ce que tu apprends.',
            'Zéro spam, zéro démarchage. C\'est un atelier, pas une place de marché.',
            'Ce qui se dit ici reste ici. On parle vrai parce qu\'on se fait confiance.',
        ];
    }

}
