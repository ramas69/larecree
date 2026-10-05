<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Programme Formation Claude 2026 — V3 fil rouge (5 étapes, 30 vidéos, ~5 h 20).
 *
 * Source unique consommée par AppFixtures (dev) ET la migration data-seed (prod).
 * Garder synchronisé : si tu changes ici, génère une nouvelle migration de re-seed.
 */
final class ClaudeProgram
{
    public const FORMATION_SLUG = 'claude-2026';
    public const FORMATION_TITLE = 'Formation Claude 2026';
    public const FORMATION_SUBTITLE = 'De « j\'effleure Claude » à « je pilote Claude »';
    public const FORMATION_DESCRIPTION = '5 étapes fil rouge · 30 vidéos · ~5 h 20 · accès à vie. Un seul projet : ton business. Chaque étape se termine par quelque chose qui existe — et qui tourne.';
    public const FORMATION_PRICE_CENTS = 39700;

    /**
     * @return list<array{
     *   title: string,
     *   slug: string,
     *   description: string,
     *   lessons: list<array{0: string, 1: int, 2: string}>
     * }>
     */
    public static function modules(): array
    {
        return [
            [
                'title' => 'Cowork : Claude te libère la main dès aujourd\'hui',
                'slug'  => 'etape-1-cowork',
                'description' => 'Tu pars de ton business — celui que tu lances ou celui que tu optimises. Livrable : ta présentation d\'offre rédigée dans ton ton, Claude réglé pour ton business, tes fichiers chargés dans ton projet de contexte. Ta première victoire, dans les 48 h.',
                'lessons' => [
                    ['Bienvenue : le fil rouge et Léa', 360, "Comment fonctionne la formation : un seul projet, ton business, et une étape = un livrable. Léa, coach, fait le parcours en démo avec toi. Le Discord, les ressources.\n\n▶ À toi : présente-toi sur le Discord + dis ce que ton business doit sortir à la fin."],
                    ['Arrêter de voir Claude comme Google', 480, "L'erreur n°1 des débutants. Chatbot vs assistant : le changement de posture. Démo : même demande, version « Google » vs « assistant ».\n\n▶ À toi : repère ta façon actuelle de parler à l'IA."],
                    ['Le game changer 2026 : c\'est quoi Cowork', 600, "La grande nouveauté pour les non-devs : Claude qui travaille sur tes fichiers et tes dossiers. Ce que ça change pour ton business. Inclus dans Pro ET Max.\n\n▶ À toi : vérifie que ton ordi est compatible."],
                    ['Installer et configurer Cowork', 660, "Compte, abonnement (ce qu'il te faut vraiment), installation pas à pas, première connexion.\n\n▶ À toi : installe Cowork et repère les 3 zones clés."],
                    ['Apprends à Claude qui tu es : la Memory', 660, "Claude qui se souvient de ton contexte : ton métier, tes clients, ton ton. Démo : Léa configure une mémoire de pro.\n\n▶ À toi : remplis ta mémoire avec ton vrai contexte business."],
                    ['Ton projet de contexte : charger tes fichiers', 720, "Un Project pour ton business : instructions, documents, exemples. Quels fichiers y mettre (et lesquels jamais).\n\n▶ À toi : crée ton projet de contexte et charge tes fichiers clés."],
                    ['La recette d\'un texte qui te ressemble : RCFE', 780, "Rôle, Contexte, Format, Exemples. Pourquoi « écris-moi un texte » ne marche jamais. Démo : un texte plat → un texte qui sonne comme toi.\n\n▶ À toi : réécris un de tes textes avec RCFE."],
                    ['On assemble : ta présentation d\'offre rédigée', 720, "Léa rédige son offre de A à Z avec son Claude réglé. Itérer, corriger, finaliser. Ta première victoire.\n\n▶ À toi : rédige ta présentation d'offre et partage ton win."],
                ],
            ],
            [
                'title' => 'Skills : écrire une fois, pour toujours',
                'slug'  => 'etape-2-skills',
                'description' => 'Ce que tu refais chaque semaine, Claude le sait déjà. Livrable : tes 2-3 skills qui tournent.',
                'lessons' => [
                    ['C\'est quoi un Skill', 540, "Les skills expliqués simplement : comment un skill transforme Claude en spécialiste de TES tâches.\n\n▶ À toi : liste 3 tâches que tu refais chaque semaine."],
                    ['Créer ton skill avec le Skill Creator', 780, "Démo : Léa crée son skill de relance client de A à Z avec le Skill Creator intégré.\n\n▶ À toi : crée ton premier skill."],
                    ['Les Plugins métier', 480, "C'est quoi un plugin (un bundle de skills). Les plugins dispo (marketing, finance, ops…) et comment choisir.\n\n▶ À toi : installe un plugin pertinent pour ton activité."],
                    ['Ton métier, ton exemple', 660, "Les mêmes gestes, adaptés à ton cas : freelance, consultant, artisan, e-commerçant. Les skills qui comptent pour chacun.\n\n▶ À toi : choisis l'exemple le plus proche de ton business."],
                    ['On assemble : tes 2-3 skills qui tournent', 600, "Tester, ajuster, ranger ta boîte à outils.\n\n▶ À toi : fais tourner tes 2-3 skills sur une vraie semaine."],
                ],
            ],
            [
                'title' => 'Connecteurs : Claude branché à tes outils',
                'slug'  => 'etape-3-connecteurs',
                'description' => 'Plus un seul copier-coller. Livrable : Claude connecté à ton calendrier, tes mails, tes fichiers (ce que les techniciens appellent MCP) — et tes données clients restent les tiennes.',
                'lessons' => [
                    ['C\'est quoi un Connecteur', 540, "Brancher Claude à tes outils : ce que ça change, ce que ça permet. Sécurité et permissions.\n\n▶ À toi : liste les outils de ton business à brancher."],
                    ['Ce qu\'on ne branche JAMAIS', 660, "Données clients sensibles, accès trop larges, vérification des faits. La séquence de confiance avant de connecter quoi que ce soit.\n\n▶ À toi : écris ta liste « jamais » et ta liste « oui »."],
                    ['Connecter ton agenda et tes mails', 720, "Démo : Léa connecte Google Agenda puis Gmail. Lire, préparer, proposer — sans rien envoyer seul.\n\n▶ À toi : connecte ton agenda et tes mails."],
                    ['Connecter tes fichiers : Drive, Notion et autres', 600, "Démo : Drive et Notion, combiner plusieurs connecteurs.\n\n▶ À toi : connecte l'outil où vivent tes fichiers."],
                    ['Ta première automatisation sur tes fichiers', 720, "Laisser Claude lire et ranger tes fichiers. Démo : organiser un dossier, traiter des documents.\n\n▶ À toi : fais ta première automatisation."],
                    ['Piloter depuis ton mobile (Dispatch)', 420, "Lancer des tâches depuis ton téléphone, entre deux rendez-vous.\n\n▶ À toi : teste Dispatch sur ton téléphone."],
                    ['On assemble : Claude branché à ton business', 600, "Récap : agenda, mails, fichiers. Plus un seul copier-coller.\n\n▶ À toi : fais tourner une journée type avec tes connecteurs."],
                ],
            ],
            [
                'title' => 'Agents : la tâche qui ne passe plus par toi',
                'slug'  => 'etape-4-agents',
                'description' => 'Rappels, relances : la tâche tourne sans toi. Livrable : ton premier agent en service — avec validation humaine avant chaque envoi. Toujours.',
                'lessons' => [
                    ['Claude qui bosse seul : c\'est quoi un agent', 600, "Les tâches récurrentes automatisées : ce qu'un agent fait, ce qu'il ne doit jamais faire seul.\n\n▶ À toi : choisis LA tâche que ton premier agent va prendre."],
                    ['Le piège du « tout à l\'IA »', 540, "Quand déléguer, quand garder la main. Pourquoi la validation humaine avant chaque envoi n'est pas négociable.\n\n▶ À toi : définis ton point de validation."],
                    ['Créer ton premier agent', 900, "Démo : Léa crée son agent de rappels de rendez-vous de A à Z — programmer, déclencher, valider.\n\n▶ À toi : crée ton agent."],
                    ['Relances et rappels : les cas d\'usage', 720, "Relances de devis, rappels de rendez-vous, suivi client. Adapter à ton métier.\n\n▶ À toi : ajoute un deuxième cas à ton agent."],
                    ['On assemble : ton agent en service', 600, "Le tester une semaine, ajuster, le laisser tourner.\n\n▶ À toi : mets ton agent en service et partage ton win."],
                ],
            ],
            [
                'title' => 'Claude Design : ta page en ligne, aujourd\'hui',
                'slug'  => 'etape-5-claude-design',
                'description' => 'Le dernier jour, ton business est en ligne. Livrable : ta page business en ligne, lien partageable, prête à mettre dans ta bio Instagram.',
                'lessons' => [
                    ['Créer en discutant : c\'est quoi Claude Design', 600, "La nouveauté 2026 pour créer visuellement sans être designer. Ce qu'on peut faire.\n\n▶ À toi : explore l'interface."],
                    ['Ton identité : couleurs, typos, style', 660, "Faire analyser ton brand existant (ou en créer un). Récupérer couleurs, typos, ton.\n\n▶ À toi : pose ton identité visuelle."],
                    ['Construire ta page business', 900, "Démo : Léa construit sa page — offre, preuve, prise de rendez-vous. Itérer en discutant.\n\n▶ À toi : construis ta page."],
                    ['Mettre ta page en ligne', 720, "Les options simples de mise en ligne, sans technique lourde. Ta page à une vraie adresse.\n\n▶ À toi : mets ta page en ligne."],
                    ['On assemble : ton business en ligne', 660, "Le lien dans ta bio, la routine de veille, la suite. Ton business est en ligne et tourne en pilote automatique.\n\n▶ À toi : partage ton lien sur le Discord."],
                ],
            ],
        ];
    }
}
