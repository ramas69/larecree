<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Programme Formation Claude 2026 — V2 (10 modules, 64 leçons).
 *
 * Source unique consommée par AppFixtures (dev) ET la migration data-seed (prod).
 * Garder synchronisé : si tu changes ici, génère une nouvelle migration de re-seed.
 */
final class ClaudeProgram
{
    public const FORMATION_SLUG = 'claude-2026';
    public const FORMATION_TITLE = 'Formation Claude 2026';
    public const FORMATION_SUBTITLE = 'De « j\'effleure Claude » à « je pilote Claude »';
    public const FORMATION_DESCRIPTION = '10 modules · 64 leçons · ~8h30 de vidéo · accès à vie. Apprentissage par cas d\'usage métier réel : chaque module résout un problème concret et se termine par un livrable.';
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
                'title' => 'Ton assistant qui te connaît',
                'slug'  => 'assistant-qui-te-connait',
                'description' => 'Arrête de tout réexpliquer à Claude. À la fin, tu as un Claude qui connaît ton métier, ton style, ton contexte. Livrable : ton assistant Claude personnel, paramétré pour TON activité.',
                'lessons' => [
                    ['Bienvenue + comment tirer le max de cette formation', 300, "Comment fonctionne la formation, le Discord, les ressources. Le seul mindset qui marche : tu construis en suivant. La règle d'or : un livrable réel à la fin de chaque module.\n\n▶ À toi : présente-toi sur le Discord + dis ton objectif."],
                    ['Arrêter de voir Claude comme Google', 420, "L'erreur n°1 des débutants. Chatbot vs assistant : le changement de posture. Démo : même demande, version « Google » vs « assistant ».\n\n▶ À toi : repère ta façon actuelle de parler à l'IA."],
                    ['Créer ton compte et t\'y retrouver en 5 min', 360, "Free vs Pro vs Max : ce qu'il te faut vraiment. Visite guidée de l'interface (web, desktop, mobile).\n\n▶ À toi : configure ton compte, repère les 3 zones clés."],
                    ['Apprends à Claude qui tu es : la Memory', 540, "Claude qui se souvient de ton contexte. Quoi lui dire sur toi, ton métier, tes clients. Démo : configurer une mémoire de pro.\n\n▶ À toi : remplis ta mémoire avec ton vrai contexte business."],
                    ['Claude qui parle TON langage : les Output Styles', 480, "Pourquoi les réponses sont parfois trop scolaires. Créer un style adapté (concis, cash, structuré, chaleureux...). Exemples par métier.\n\n▶ À toi : crée ton style signature."],
                    ['Quand demander à Claude de réfléchir à fond', 480, "Réponse rapide vs réflexion approfondie (Extended Thinking). Le contrôle de l'effort (nouveauté Opus 4.8). Démo sur un vrai cas vs un cas simple.\n\n▶ À toi : teste sur une décision réelle de ton activité."],
                    ['On assemble : ton assistant prêt à l\'emploi', 540, "Combiner Memory + Style sur un vrai cas. Démo : déléguer une tâche réelle.\n\n▶ À toi : délègue une vraie tâche, partage ton premier win."],
                ],
            ],
            [
                'title' => 'Rédiger & communiquer 10× plus vite',
                'slug'  => 'rediger-communiquer',
                'description' => 'Tes emails, posts, propositions te prennent des heures. À la fin, tu produis des écrits pros en minutes, qui te ressemblent. Livrable : ta bibliothèque personnelle de prompts de rédaction.',
                'lessons' => [
                    ['Pourquoi tes textes IA sonnent faux', 480, "Les 7 erreurs qui font que ça sent l'IA. Pourquoi « écris-moi un post » ne marche jamais.\n\n▶ À toi : repère l'erreur dans 3 exemples."],
                    ['La recette d\'un texte qui te ressemble : RCFE', 720, "Rôle, Contexte, Format, Exemples : les 4 piliers. Démo : un texte plat → un texte qui claque.\n\n▶ À toi : réécris un de tes textes avec RCFE."],
                    ['Donner du contexte comme un pro', 540, "Pourquoi le contexte change tout. Coller documents, exemples, données.\n\n▶ À toi : refais un texte avec contexte riche."],
                    ['La technique secrète : montrer des exemples', 480, "Le few-shot expliqué simplement. 1-2 exemples = résultats x3.\n\n▶ À toi : ajoute des exemples à un prompt."],
                    ['Le dialogue : ne jamais s\'arrêter au premier jet', 420, "Corriger, affiner, relancer. Les phrases magiques pour améliorer.\n\n▶ À toi : améliore un résultat en 3 itérations."],
                    ['Faire parler tes données : tableaux et graphiques', 420, "Générer tableaux et visualisations dans le chat.\n\n▶ À toi : transforme des données en tableau clair."],
                    ['Tes 10 templates de rédaction prêts à l\'emploi', 420, "Présentation des templates fournis. Les adapter à ton métier.\n\n▶ À toi : adapte 3 templates à tes besoins."],
                ],
            ],
            [
                'title' => 'Ton cerveau externe',
                'slug'  => 'cerveau-externe',
                'description' => 'Tu croules sous les documents et les recherches. À la fin, Claude devient ton analyste : il digère, synthétise, compare. Livrable : ton premier vrai rapport d\'analyse (sur tes propres données).',
                'lessons' => [
                    ['Arrête de tout réexpliquer : c\'est quoi un Project', 480, "Le problème : repartir de zéro à chaque conversation. Quand créer un Project vs une simple conversation.\n\n▶ À toi : identifie 3 Projects utiles pour toi."],
                    ['Créer ton premier Project pas à pas', 600, "Démo : créer, nommer, décrire, organiser. Les instructions personnalisées du Project.\n\n▶ À toi : crée ton premier Project."],
                    ['Nourrir Claude : ajouter fichiers et contexte', 540, "Quels fichiers ajouter. Le 1M tokens : donner des documents énormes.\n\n▶ À toi : ajoute tes documents clés."],
                    ['Un Project par client / par projet', 480, "Structurer par client, par activité. Démo : un Project « client X » complet.\n\n▶ À toi : crée un Project pour un vrai client."],
                    ['Analyser et décider : faire parler tes données', 480, "Synthétiser un gros document, comparer des options. Démo : un rapport d'analyse à partir de données brutes.\n\n▶ À toi : produis une analyse sur tes propres données."],
                    ['On assemble : Memory + Projects, ton cerveau externe', 420, "Comment Memory et Projects travaillent ensemble.\n\n▶ À toi : organise tes Projects + partage ton rapport."],
                ],
            ],
            [
                'title' => 'Produire de vrais livrables',
                'slug'  => 'produire-livrables',
                'description' => 'Tu veux que Claude PRODUISE, pas juste discute. À la fin, tu crées des documents, visuels et présentations prêts à envoyer. Livrable : un livrable client fini, de A à Z.',
                'lessons' => [
                    ['Discuter vs créer : c\'est quoi un Artifact', 420, "La différence entre parler et produire. Les types d'Artifacts (doc, visuel, page, tableau).\n\n▶ À toi : génère ton premier Artifact."],
                    ['Créer des documents pros', 540, "Rapports, propositions, articles. Mise en forme et structure.\n\n▶ À toi : crée un document utile à ton activité."],
                    ['Créer des visuels et des pages', 600, "Générer pages web, visuels simples. Modifier en temps réel.\n\n▶ À toi : crée une page ou un visuel."],
                    ['Itérer vite : 3 versions en 5 minutes', 480, "Demander des ajustements précis.\n\n▶ À toi : itère sur ton livrable jusqu'à le finaliser."],
                    ['Partager et exporter', 420, "Options de partage, récupérer ton travail.\n\n▶ À toi : exporte et partage un Artifact."],
                    ['On assemble : ton livrable client de A à Z', 420, "Les pièges à éviter.\n\n▶ À toi : crée un livrable complet, prêt à envoyer."],
                ],
            ],
            [
                'title' => 'Bien utiliser Claude : le réflexe 4E',
                'slug'  => 'reflexe-4e',
                'description' => 'Le piège des débutants : tout déléguer à l\'IA, même ce qu\'il ne faut pas. À la fin, tu sais quand faire confiance à Claude et quand garder la main. C\'est ce qui sépare l\'amateur du pro. Livrable : ta grille de décision personnelle « IA ou pas IA ».',
                'lessons' => [
                    ['Le piège du « tout à l\'IA »', 420, "Pourquoi déléguer aveuglément te dessert. Des exemples réels où l'IA fait perdre du temps ou de la crédibilité. Le réflexe à acquérir avant d'aller plus loin.\n\n▶ À toi : note 3 tâches que tu délègues déjà sans réfléchir."],
                    ['Effective : choisir la bonne tâche pour l\'IA', 480, "Les tâches où Claude excelle vraiment. Les tâches où il est moyen voire dangereux. Démo : trier 10 tâches de ton quotidien.\n\n▶ À toi : classe tes tâches en « IA / humain / mixte »."],
                    ['Efficient : ne pas sur-utiliser l\'IA', 420, "Quand c'est plus rapide de le faire toi-même. Le coût caché du « je vérifie tout ce que l'IA a fait ».\n\n▶ À toi : repère 1 tâche où l'IA te ralentit."],
                    ['Ethical & Safe : données, confidentialité, vérification', 540, "Ce qu'on ne met JAMAIS dans Claude (données clients sensibles, etc.). Pourquoi toujours vérifier les chiffres, faits, citations. La nouveauté Opus 4.8 : Claude signale mieux quand il n'est pas sûr.\n\n▶ À toi : définis tes règles de confidentialité."],
                    ['On assemble : ta grille de décision IA', 420, "Construire ta grille perso « quand j'utilise Claude ». Le réflexe pro qui te démarque.\n\n▶ À toi : remplis ta grille et partage-la."],
                ],
            ],
            [
                'title' => 'Déléguer le travail répétitif (Cowork 1/2)',
                'slug'  => 'deleguer-repetitif',
                'description' => '2-3h par jour partent dans des tâches répétitives. À la fin, tu as transformé Claude en spécialiste de TES tâches grâce à Cowork et aux skills. Livrable : ton premier skill métier sur-mesure.',
                'lessons' => [
                    ['Le game changer 2026 : c\'est quoi Cowork', 480, "La grande nouveauté pour les non-devs. Ce que ça change (fichiers, dossiers, apps). Inclus dans Pro ET Max (pas besoin de payer plus).\n\n▶ À toi : vérifie si ton ordi est compatible."],
                    ['Installer et configurer Cowork', 540, "Installation pas à pas, première connexion.\n\n▶ À toi : installe Cowork."],
                    ['Ta première automatisation sur tes fichiers', 600, "Laisser Claude lire/modifier tes fichiers locaux. Démo : organiser un dossier, traiter des fichiers. Permissions et sécurité (rappel du réflexe 4E).\n\n▶ À toi : fais ta première automatisation simple."],
                    ['C\'est quoi un Skill', 420, "Les skills expliqués simplement. Comment un skill transforme Claude en spécialiste.\n\n▶ À toi : identifie 3 skills utiles pour ton métier."],
                    ['Créer ton skill avec le Skill Creator', 600, "Utiliser le Skill Creator intégré. Démo : un skill métier de A à Z.\n\n▶ À toi : crée ton premier skill."],
                    ['Les Plugins métier', 300, "C'est quoi un plugin (bundle de skills). Les plugins dispo (marketing, finance, ops...).\n\n▶ À toi : installe un plugin pertinent."],
                    ['On assemble : ton kit de skills perso', 300, "Construire sa boîte à outils.\n\n▶ À toi : liste les skills à créer pour ton activité."],
                ],
            ],
            [
                'title' => 'Ton équipe d\'agents (Cowork 2/2)',
                'slug'  => 'equipe-agents',
                'description' => 'Et si Claude bossait pendant que tu dors ? À la fin, tu as connecté tes outils et créé des routines qui tournent toutes seules. Livrable : ta première routine automatique (reporting, veille ou emails).',
                'lessons' => [
                    ['Connecter Claude à tes outils : c\'est quoi un Connector', 420, "Brancher Drive, Gmail, Slack, Notion. Sécurité et permissions.\n\n▶ À toi : identifie les connecteurs utiles pour toi."],
                    ['Connecter Google Drive et Gmail', 540, "Démo : connecter Drive, puis Gmail.\n\n▶ À toi : connecte tes premiers outils."],
                    ['Connecter Slack, Notion et autres', 480, "Démo : Slack et Notion, combiner plusieurs connecteurs.\n\n▶ À toi : connecte un outil que tu utilises."],
                    ['Claude qui bosse seul : c\'est quoi une Routine', 480, "Les tâches récurrentes automatisées.\n\n▶ À toi : imagine 3 routines pour ton activité."],
                    ['Créer ta première routine', 600, "Démo : une routine de A à Z, programmer et déclencher.\n\n▶ À toi : crée une routine simple."],
                    ['Cas d\'usage : reporting, veille, emails', 300, "Routine de reporting hebdo, veille concurrentielle, tri d'emails.\n\n▶ À toi : mets en place une routine pro utile."],
                    ['On assemble : piloter depuis ton mobile (Dispatch)', 300, "Lancer des tâches depuis ton téléphone.\n\n▶ À toi : teste Dispatch sur ton tel."],
                ],
            ],
            [
                'title' => 'Ton image de marque sans designer',
                'slug'  => 'image-marque',
                'description' => 'Tu n\'es pas designer mais tu as besoin de visuels pros. À la fin, tu crées ton identité, tes slides, tes maquettes en discutant avec Claude Design. Livrable : ton kit visuel de marque (deck + landing + identité).',
                'lessons' => [
                    ['Créer en discutant : c\'est quoi Claude Design', 480, "La nouveauté 2026 pour créer visuellement. Ce qu'on peut faire (decks, mockups, landing...).\n\n▶ À toi : explore l'interface."],
                    ['Ton premier projet : un pitch deck', 600, "Démo : créer une présentation de A à Z.\n\n▶ À toi : crée un deck sur ton sujet."],
                    ['Créer une landing page ou un mockup', 540, "Démo : générer une maquette, itérer.\n\n▶ À toi : crée une maquette pour ton projet."],
                    ['Utiliser ton brand : design system extraction', 480, "Faire analyser ton site/brand existant. Récupérer couleurs, typos, style.\n\n▶ À toi : extrais ton design system."],
                    ['Peaufiner visuellement : inline edits & sliders', 420, "Modifier visuellement, pas qu'en texte.\n\n▶ À toi : ajuste un de tes designs."],
                    ['On assemble : exporter vers Canva et Figma', 360, "Récupérer ton travail dans Canva / Figma.\n\n▶ À toi : exporte un design et finalise-le."],
                ],
            ],
            [
                'title' => 'Claude pour TON métier',
                'slug'  => 'claude-metier',
                'description' => 'Jusqu\'ici tu as appris les outils. Maintenant on les applique à TON métier précis. Choisis ton parcours — chacun montre un workflow complet de bout en bout. Livrable : ton workflow métier complet, prêt à l\'emploi.',
                'lessons' => [
                    ['Comment utiliser ce module', 240, "Les 4 parcours : choisis le tien (ou regarde-les tous). Comment adapter un parcours à ta variante de métier.\n\n▶ À toi : choisis ton parcours principal."],
                    ['Parcours Freelance créa / marketing', 720, "Workflow complet : de la prise de brief client au livrable. Combiner prompts + Projects + Artifacts + Design. Démo réelle : une mission de A à Z (contenu, visuel, livraison).\n\n▶ À toi : reproduis le workflow sur une de tes missions."],
                    ['Parcours Coach / formateur', 720, "Workflow complet : créer un programme, ses supports, sa communauté. Combiner Projects + Artifacts + Cowork. Démo réelle : construire un mini-programme avec ses ressources.\n\n▶ À toi : reproduis sur un de tes programmes."],
                    ['Parcours Commerce / service local', 720, "Workflow complet : devis, relation client, présence en ligne. Combiner prompts + routines + Design. Démo réelle : automatiser devis + réponses + post réseaux.\n\n▶ À toi : reproduis sur ton activité."],
                    ['Parcours Consultant / indépendant', 720, "Workflow complet : proposition commerciale, analyse, livrable. Combiner Projects + cerveau externe + Artifacts. Démo réelle : une proposition + une analyse client.\n\n▶ À toi : reproduis sur une de tes missions."],
                    ['On assemble : ton système métier', 240, "Récap : assembler les outils en UN workflow récurrent. Comment l'affiner avec le temps.\n\n▶ À toi : documente ton workflow métier complet."],
                ],
            ],
            [
                'title' => 'Lance ton premier site (et goûte à l\'app)',
                'slug'  => 'lance-ton-site',
                'description' => 'Le grand final. À la fin, tu as un vrai site en ligne à ton nom, et tu as touché du doigt la création d\'app web — sans coder à la main. C\'est une initiation solide, pas une formation de développeur. Livrable : ton site en ligne + une mini-app web fonctionnelle.',
                'lessons' => [
                    ['La vision : Claude comme système, pas comme outil', 360, "Penser en workflows et systèmes. La vision d'ensemble de tout ce qu'on a appris. Ce qu'on va construire dans ce module (et ses limites honnêtes).\n\n▶ À toi : dessine ce que tu veux mettre en ligne."],
                    ['Préparer ton site : structure et contenu', 480, "Définir les pages, le message, le contenu. Préparer tout avec Claude avant de construire.\n\n▶ À toi : prépare la structure de ton site."],
                    ['Construire ton site avec Claude', 720, "Démo complète : générer un site vitrine simple. Combiner Design + Artifacts.\n\n▶ À toi : construis ton site."],
                    ['Mettre ton site en ligne', 600, "Les options simples de mise en ligne (sans technique lourde). Démo : ton site accessible à une vraie adresse.\n\n▶ À toi : mets ton site en ligne."],
                    ['Goûter à l\'app web : créer une mini-app', 720, "La différence entre un site et une app. Démo : une mini-app web simple mais fonctionnelle. Honnêteté : ce qu'on peut faire seul vs ce qui demande d'aller plus loin.\n\n▶ À toi : crée ta première mini-app."],
                    ['Maintenir et faire évoluer', 300, "Claude évolue tous les mois : rester à jour. Le canal veille du Discord.\n\n▶ À toi : mets en place ta routine de veille."],
                    ['On assemble : ta feuille de route 90 jours', 300, "Récap de toute la formation. Ton plan d'action pour les 90 prochains jours. Pour aller plus loin : la formation Build avancée (à venir).\n\n▶ À toi : écris ton plan d'action et partage-le."],
                ],
            ],
        ];
    }
}
