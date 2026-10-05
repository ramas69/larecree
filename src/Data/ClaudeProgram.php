<?php

declare(strict_types=1);

/**
 * Programme Formation Claude 2026-27 — V3 (fil rouge, 5 étapes, 30 vidéos, ~5h).
 *
 * Source unique consommée par AppFixtures (dev) ET la migration data-seed (prod).
 * Garder synchronisé : si tu changes ici, génère une nouvelle migration de re-seed.
 *
 * NOTE V3 : les 10 modules V2 sont dissous dans 5 étapes fil rouge. Chaque
 * vidéo = 1 action de l'élève + un livrable. Léa (coach) sert de fil de démo.
 */

namespace App\Data;

final class ClaudeProgram
{
    public const FORMATION_SLUG = 'claude-2026';
    public const FORMATION_TITLE = 'Formation Claude 2026-27';
    public const FORMATION_SUBTITLE = 'De « j\'effleure Claude » à « je pilote Claude »';
    public const FORMATION_DESCRIPTION = 'Fil rouge : 5 étapes · 30 vidéos · ~5h de vidéo · accès à vie. Un seul projet : ton business. Chaque étape se termine par un livrable réel — ton offre, tes skills, tes connecteurs, ton agent, ta page en ligne.';
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
                'title' => 'Étape 1 · Cowork — Claude te libère la main dès aujourd\'hui',
                'slug'  => 'etape-1-cowork',
                'description' => 'La première victoire, dans les 48 h. Tu sors avec : ta présentation d\'offre rédigée dans ton ton, Claude réglé pour TON business, tes fichiers chargés — et ton projet de contexte monté.',
                'lessons' => [
                    ['Crée ton compte Claude', 480, "Quel plan choisir (Pro suffit), premiers réglages de langue et de style. Visite rapide de l'interface.\n\n▶ À toi : configure ton compte, repère les 3 zones clés."],
                    ['Règle Claude pour TON business', 480, "La Memory et le style par défaut : Claude se souvient de qui tu es, de ton métier, de ton ton. Ne plus jamais réexpliquer.\n\n▶ À toi : remplis ta mémoire avec ton vrai contexte — comme Léa, coach, qui fixe son vouvoiement et son ton chaleureux."],
                    ['La fenêtre chat vs le projet', 600, "Quand utiliser une conversation simple, quand créer un Project. La différence qui fait gagner des heures.\n\n▶ À toi : identifie 3 Projects utiles pour ton business."],
                    ['Ta première vraie tâche', 600, "Choisir UNE tâche réelle de ton business et la formuler comme à un stagiaire : le trio rôle / contexte / format (la méthode RCFE en action). Démo Léa : son texte de présentation aux nouveaux prospects.\n\n▶ À toi : rédige ta présentation d'offre avec le prompt du cours."],
                    ['Le va-et-vient', 600, "Corriger sans repartir de zéro : « moins long », « garde le ton », « reprends le point 2 ». Une correction = un chiffre ou une zone pointée.\n\n▶ À toi : améliore ton texte en 3 itérations, jusqu'à ce qu'il te ressemble."],
                    ['Ton projet de contexte', 720, "Charger TES fichiers dans un Project : ta fiche de tarifs, tes emails types, ton offre. C'est ça, du contexte : ce que Claude voit à chaque fois. (On ne prompte plus : on construit du contexte.)\n\n▶ À toi : monte ton projet de contexte — ton actif le plus durable."],
                    ['Les erreurs qui coûtent', 480, "Recopier sans relire, donner des données sensibles, attendre la perfection du premier essai. Les 12 erreurs que je vois chez tous les débutants — et le réflexe anti-chaque.\n\n▶ À toi : note tes 3 réflexes de vérification."],
                    ['On ne prompte plus : on construit du contexte', 720, "La leçon 2026 : le prompt n'est qu'une ligne, le vrai actif est le contexte (projet, skills, mémoire). Cas d'école en direct : un prompt « tout-en-un » d'affiche de marque, analysé et réparé en 2 prompts.\n\n▶ À toi : livre ta présentation d'offre finale — c'est ton livrable d'étape."],
                ],
            ],
            [
                'title' => 'Étape 2 · Skills — écrire une fois, pour toujours',
                'slug'  => 'etape-2-skills',
                'description' => 'Ce que tu refais chaque semaine, Claude le sait déjà. Tu sors avec : tes 2-3 skills qui tournent sur ton vrai travail.',
                'lessons' => [
                    ['Repère ce qui se répète', 480, "Balayer ta semaine : les 3 tâches hebdo que tu refais tout le temps. Le critère de choix : « je pourrais le coller dans Claude sans réexpliquer ».\n\n▶ À toi : liste tes 3 candidates."],
                    ['Ta première skill', 720, "Construire de A à Z la skill « relance prospect » de Léa : rôle, contexte à DEMANDER (ne jamais deviner), règles non négociables, format littéral, cas limites.\n\n▶ À toi : transpose la skill sur TON service."],
                    ['La structure qui marche', 600, "Anatomie d'un SKILL.md : nom, description qui déclenche, corps concis (<500 lignes), règles avant le format. Pourquoi les interdits font plus que les souhaits.\n\n▶ À toi : passe ta skill au crayon rouge."],
                    ['Durcir une skill', 720, "Bornes chiffrées, cas limites (« si plus de 3 mois de silence… »), boucle de vérification, boucle de validation humaine. Un prompt sans SI est une démo.\n\n▶ À toi : ajoute les 2 SI de survie à ta skill."],
                    ['Ta boîte à skills', 480, "Organiser, nommer, tester avec 3-5 formulations différentes. Quand fusionner, quand couper. Ta boîte devient ta bibliothèque « prompts qui marchent ».\n\n▶ À toi : 2-3 skills validées et testées — c'est ton livrable d'étape."],
                ],
            ],
            [
                'title' => 'Étape 3 · Connecteurs — Claude branché à tes outils',
                'slug'  => 'etape-3-connecteurs',
                'description' => 'Plus un seul copier-coller. Tu sors avec : Claude connecté à ton calendrier, tes mails, tes fichiers — et tes données clients restent les tiennes.',
                'lessons' => [
                    ['C\'est quoi un connecteur', 300, "L'image de la prise universelle entre Claude et tes outils (ce que les techniciens appellent MCP — tu n'en auras jamais besoin). Le modèle c'est le cerveau, les connecteurs sont les mains, tes skills sont les gestes.\n\n▶ À toi : liste les 2 outils que Claude devrait voir."],
                    ['Branche ton calendrier, pas à pas', 720, "Démo écran complet : répertoire, connexion, autorisations. Règle d'or : commencer en « approbation requise » pour tout ce qui écrit.\n\n▶ À toi : branche ton calendrier."],
                    ['Branche ta boîte mail', 720, "Lecture seule d'abord. Ce que ça change : Claude voit les prospects sans réponse, prépare — n'envoie jamais seul.\n\n▶ À toi : branche ta boîte en lecture seule."],
                    ['Branche tes fichiers', 600, "Drive / documents : ta fiche de tarifs, ton offre, tes messages types deviennent visibles de Claude. Ce qui est stable va dans le contexte (étape 1), ce qui change chaque jour va dans un connecteur.\n\n▶ À toi : branche ton stockage."],
                    ['Ce qu\'on ne branche JAMAIS', 480, "Données de paiement, identité, santé, données clients sensibles sans accord. Le réflexe 4E (Effective, Efficient, Ethical, Safe) version production. Ta règle simple à retenir.\n\n▶ À toi : écris ta règle de confidentialité personnelle."],
                    ['Le flux branché', 720, "La démo du module : lire → préparer → proposer → attendre validation. Léa : Claude repère la cliente qui a sauté 2 séances et prépare le rappel doux. Zéro copier-coller.\n\n▶ À toi : fais tourner ton premier flux complet."],
                    ['Quand ça casse', 480, "Déconnexion, permissions perdues, trop de connecteurs actifs (au-delà de 10 : mode « à la demande »). Les réflexes de déblocage.\n\n▶ À toi : teste un scénario de panne — c'est ton livrable d'étape."],
                ],
            ],
            [
                'title' => 'Étape 4 · Agents — la tâche qui ne passe plus par toi',
                'slug'  => 'etape-4-agents',
                'description' => 'Ton premier agent en service. Tu sors avec : rappels et relances qui partent seuls — validés par toi avant chaque envoi. Toujours.',
                'lessons' => [
                    ['Agent ≠ chat', 360, "Le chat attend, l'agent agit selon un horaire. L'échelle d'autonomie : plus l'action est réversible, plus Claude peut agir seul ; plus elle est visible, plus il te demande.\n\n▶ À toi : choisis TA tâche — répétitive, bénigne, vérifiable."],
                    ['Choisis TA tâche', 480, "Le critère des 3 conditions. Léa : les rappels de séance. Les mauvaises candidates : ce qui demande un jugement qui change, ce qui envoie à ta place, ce qui n'a jamais été fait à la main.\n\n▶ À toi : valide ta tâche contre les 3 conditions."],
                    ['Construis ton agent', 840, "Pas à pas : l'horaire (« chaque soir à 18 h »), la préparation (jamais l'envoi), la mise en file de validation, le TON depuis ton projet, les bornes (max 3 phrases), les 2 SI de survie (jour vide, cas ambigu).\n\n▶ À toi : construis le tien sur ta vraie tâche."],
                    ['Fais-le tourner', 600, "Premières exécutions : regarder, corriger, durcir. Le prompt autonome (personne ne sera là pour répondre) : règles par défaut, signalement des manques, « ce qui demande mon attention » en 3 lignes.\n\n▶ À toi : 3 exécutions contrôlées — c'est ton livrable d'étape."],
                    ['Le vrai coût en production', 600, "Chaque étape consomme des tokens : /usage, la part des skills et des connecteurs, quand l'IA coûte plus cher que 3 minutes à la main. Ce que les vendeurs ne montrent pas.\n\n▶ À toi : mesure le coût réel de ton agent sur une semaine."],
                ],
            ],
            [
                'title' => 'Étape 5 · Claude Design — ta page en ligne, aujourd\'hui',
                'slug'  => 'etape-5-claude-design',
                'description' => 'Le grand final. Tu sors avec : ta page business en ligne, lien partageable, prête à mettre dans ta bio Instagram.',
                'lessons' => [
                    ['Le brouillon qui parle', 600, "Partir de SES mots : ce que tu vends, à qui, la promesse (le contenu est ton affaire, le design est l'affaire de Claude). Démo Léa : sa vitrine « coaching ».\n\n▶ À toi : rédige ton brief de page."],
                    ['Génère ta première version', 600, "Claude Design : deck, landing, mockups. La contrainte qui fait « pro » : une seule couleur d'accent, un seul CTA au-dessus de la ligne de flottaison.\n\n▶ À toi : génère la tienne."],
                    ['Les 3 corrections qui font pro', 720, "Hiérarchie, contraste, un seul bouton. Les interdits AVANT la génération : carrousel, compteur de places, phrases marketing génériques.\n\n▶ À toi : applique les 3 corrections."],
                    ['Le texte qui convertit', 600, "Titres (le résultat, pas le sujet), preuve sociale, section prix. Ce que tu as appris en étape 1 (RCFE) s'applique à chaque mot de ta page.\n\n▶ À toi : finalise tes textes."],
                    ['Mets-la en ligne, pas à pas', 720, "Hébergement simple + domaine + vérifier sur mobile. Léa : sa page est en ligne, le lien va dans sa bio Instagram.\n\n▶ À toi : TA page en ligne, lien partageable — c'est ton livrable final. 🎉"],
                ],
            ],
        ];
    }
}
