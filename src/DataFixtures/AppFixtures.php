<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Enrollment;
use App\Entity\EnrollmentSource;
use App\Entity\Formation;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\Module;
use App\Entity\Resource;
use App\Entity\ResourceType;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->makeUser('admin@larecreetech.com', 'admin', 'Admin', 'Rama', ['ROLE_ADMIN']);
        $vip   = $this->makeUser('vip@larecreetech.com', 'vip', 'VIP', 'Test', ['ROLE_VIP']);
        $rama  = $this->makeUser('rama@hallia.ai', 'rama', 'Rama', 'Soumare', ['ROLE_STUDENT']);

        $manager->persist($admin);
        $manager->persist($vip);
        $manager->persist($rama);

        [$claude, $claudeLessons] = $this->seedClaudeFormation($manager);
        $this->seedManusFormation($manager);

        // Rama : enrollment Stripe sur Claude + progression M01+M02 done, M03L1 done, M03L2 47 %
        $ramaClaude = $this->enroll($manager, $rama, $claude, EnrollmentSource::Stripe, 39700, 'cs_test_claude', 'pi_test_claude');
        $this->markModuleCompleted($manager, $ramaClaude, $claudeLessons[0]);
        $this->markModuleCompleted($manager, $ramaClaude, $claudeLessons[1]);
        $this->markLessonCompleted($manager, $ramaClaude, $claudeLessons[2][0]);
        $this->recordPartialWatch($manager, $ramaClaude, $claudeLessons[2][1], 47);

        // VIP : enrollment Vip gratuit sur Claude
        $this->enroll($manager, $vip, $claude, EnrollmentSource::Vip);

        $manager->flush();
    }

    /**
     * @return array{0: Formation, 1: array<int, array<int, Lesson>>}
     */
    private function seedClaudeFormation(ObjectManager $manager): array
    {
        $claude = (new Formation())
            ->setSlug('claude-2026')
            ->setTitle('Formation Claude 2026')
            ->setSubtitle('De « j\'effleure Claude » à « je pilote Claude »')
            ->setDescription('8 modules · 52 leçons · ~7h de vidéo · accès à vie. Apprentissage par cas d\'usage métier réel : chaque module se termine par un livrable concret.')
            ->setPriceCents(39700)
            ->setCurrency('EUR')
            ->setPublished(true)
            ->setDisplayOrder(1);
        $manager->persist($claude);

        return [$claude, $this->seedClaudeContent($manager, $claude)];
    }

    /**
     * Vrai programme Formation Claude 2026 — 8 modules, 52 leçons.
     *
     * @return array<int, array<int, Lesson>>
     */
    private function seedClaudeContent(ObjectManager $manager, Formation $formation): array
    {
        $modules = [
            [
                'title' => 'Ton assistant qui te connaît',
                'slug'  => 'assistant-qui-te-connait',
                'description' => 'Arrête de tout réexpliquer à Claude à chaque fois. À la fin, tu as un Claude qui connaît ton métier, ton style, ton contexte — comme un assistant qui bosse avec toi depuis 6 mois.',
                'lessons' => [
                    ['Bienvenue + comment tirer le max de cette formation', 300, "Comment fonctionne la formation, le Discord, les ressources. Le seul mindset qui marche : tu construis en suivant, tu ne regardes pas. La règle d'or : à la fin de chaque module, tu as un livrable réel.\n\n▶ À toi : présente-toi sur le Discord + dis ton objectif."],
                    ['Arrêter de voir Claude comme Google', 420, "L'erreur n°1 des débutants (poser des questions comme à un moteur de recherche). Chatbot vs assistant : le changement de posture. Démo : la même demande, version « Google » vs version « assistant ».\n\n▶ À toi : repère ta façon actuelle de parler à l'IA."],
                    ['Créer ton compte et t\'y retrouver en 5 min', 360, "Free vs Pro : ce qu'il te faut vraiment selon ton usage. Visite guidée de l'interface (web, desktop, mobile). Où tout se passe.\n\n▶ À toi : configure ton compte et repère les 3 zones clés."],
                    ['Apprends à Claude qui tu es : la Memory', 540, "Le truc qui change tout : Claude qui se souvient de ton contexte. Quoi lui dire sur toi, ton métier, tes clients, tes objectifs. Démo : configurer une mémoire de pro.\n\n▶ À toi : remplis ta mémoire avec ton vrai contexte business."],
                    ['Claude qui parle TON langage : les Output Styles', 480, "Pourquoi les réponses sont parfois trop longues/scolaires. Créer un style adapté à toi (concis, cash, structuré, chaleureux...). Exemples par métier (freelance, coach, consultant, commerçant...).\n\n▶ À toi : crée ton style signature."],
                    ['Quand demander à Claude de réfléchir à fond : Extended Thinking', 480, "Différence entre réponse rapide et réflexion approfondie. Quand l'activer (décision complexe, stratégie, analyse) et quand c'est inutile. Démo sur un vrai cas vs un cas simple.\n\n▶ À toi : teste sur une décision réelle de ton activité."],
                    ['On assemble : ton assistant prêt à l\'emploi', 540, "On combine Memory + Style sur un vrai cas de ton quotidien. Démo : déléguer une tâche réelle à ton assistant configuré. Ce que tu as gagné, ce qui arrive au module 02.\n\n▶ À toi : délègue une vraie tâche, partage ton premier win."],
                ],
            ],
            [
                'title' => 'Rédiger & communiquer 10× plus vite',
                'slug'  => 'rediger-communiquer',
                'description' => 'Tes emails, posts, propositions et contenus te prennent des heures. À la fin, tu produis des écrits pros en quelques minutes, qui te ressemblent vraiment.',
                'lessons' => [
                    ['Pourquoi tes textes IA sonnent faux', 480, "Les 7 erreurs qui font que ça sent l'IA à plein nez. Pourquoi « écris-moi un post » ne marchera jamais. Le principe : Claude n'est pas devin.\n\n▶ À toi : repère l'erreur dans 3 exemples donnés."],
                    ['La recette d\'un texte qui te ressemble : la structure RCFE', 720, "Rôle, Contexte, Format, Exemples : les 4 piliers. Démo : transformer un texte plat en texte qui claque. Quand utiliser chaque pilier.\n\n▶ À toi : réécris un de tes textes avec RCFE."],
                    ['Donner du contexte comme un pro', 540, "Pourquoi le contexte change tout. Le bon niveau de détail (ni trop, ni trop peu). Coller des documents, exemples, données.\n\n▶ À toi : refais un texte avec contexte riche."],
                    ['La technique secrète : montrer des exemples', 480, "Le few-shot expliqué simplement. 1-2 exemples = résultats x3. Démo concrète sur un email/post.\n\n▶ À toi : ajoute des exemples à un prompt."],
                    ['Le dialogue : ne jamais s\'arrêter au premier jet', 420, "Corriger, affiner, relancer. Les phrases magiques pour améliorer un texte. Démo : un texte moyen → excellent en 3 échanges.\n\n▶ À toi : améliore un résultat en 3 itérations."],
                    ['Faire parler tes données : tableaux et graphiques', 420, "Générer tableaux et visualisations dans le chat. Cas d'usage : comparatif, reporting, synthèse chiffrée. Démo.\n\n▶ À toi : transforme des données en tableau clair."],
                    ['Tes 10 templates de rédaction prêts à l\'emploi', 420, "Présentation des templates fournis. Les adapter à ton métier. Construire ta propre bibliothèque.\n\n▶ À toi : adapte 3 templates à tes besoins."],
                ],
            ],
            [
                'title' => 'Ton cerveau externe',
                'slug'  => 'cerveau-externe',
                'description' => 'Tu croules sous les documents, les données, les recherches à faire. À la fin, Claude devient ton analyste : il digère, synthétise, compare et t\'aide à décider.',
                'lessons' => [
                    ['Arrête de tout réexpliquer : c\'est quoi un Project', 480, "Le problème : repartir de zéro à chaque conversation. La solution : les Projects comme espaces persistants. Quand créer un Project vs une simple conversation.\n\n▶ À toi : identifie 3 Projects utiles pour toi."],
                    ['Créer ton premier Project pas à pas', 600, "Démo : créer, nommer, décrire, organiser. Les instructions personnalisées du Project.\n\n▶ À toi : crée ton premier Project."],
                    ['Nourrir Claude : ajouter fichiers et contexte', 540, "Quels fichiers ajouter (docs, données, références). Comment Claude les utilise. Le 1M tokens : donner des documents énormes.\n\n▶ À toi : ajoute tes documents clés."],
                    ['Un Project par client / par projet', 480, "Structurer par client, par activité. Démo : un Project « client X » complet. Gagner du temps sur chaque mission.\n\n▶ À toi : crée un Project pour un vrai client/projet."],
                    ['Analyser et décider : faire parler tes données', 480, "Synthétiser un gros document. Comparer des options, visualiser avec inline charts. Démo : un rapport d'analyse à partir de données brutes.\n\n▶ À toi : produis une analyse sur tes propres données."],
                    ['On assemble : Memory + Projects, ton cerveau externe', 420, "Comment Memory et Projects travaillent ensemble. Organiser proprement ton espace de travail. Transition vers les livrables.\n\n▶ À toi : organise tous tes Projects + partage ton rapport."],
                ],
            ],
            [
                'title' => 'Produire de vrais livrables',
                'slug'  => 'produire-livrables',
                'description' => 'Tu veux que Claude PRODUISE, pas juste discute. À la fin, tu crées des documents, visuels et présentations pros, prêts à envoyer à un client.',
                'lessons' => [
                    ['Discuter vs créer : c\'est quoi un Artifact', 420, "La différence entre parler et produire. Les types d'Artifacts (doc, visuel, page, tableau). Quand Claude crée un Artifact.\n\n▶ À toi : génère ton premier Artifact."],
                    ['Créer des documents pros', 540, "Rapports, propositions, articles. Mise en forme et structure.\n\n▶ À toi : crée un document utile à ton activité."],
                    ['Créer des visuels et des pages', 600, "Générer pages web, visuels simples. Modifier en temps réel. Cas d'usage non-dev.\n\n▶ À toi : crée une page ou un visuel."],
                    ['Itérer vite : 3 versions en 5 minutes', 480, "Demander des ajustements précis. Comparer et choisir.\n\n▶ À toi : itère sur ton livrable jusqu'à le finaliser."],
                    ['Partager et exporter', 420, "Options de partage. Récupérer ton travail (copier, télécharger). Réutiliser un Artifact.\n\n▶ À toi : exporte et partage un Artifact."],
                    ['On assemble : ton livrable client de A à Z', 420, "Les pièges à éviter avec les Artifacts. Quand ça ne marche pas et pourquoi.\n\n▶ À toi : crée un livrable complet, prêt à envoyer."],
                ],
            ],
            [
                'title' => 'Déléguer le travail répétitif',
                'slug'  => 'deleguer-repetitif',
                'description' => '2-3h par jour partent dans des tâches répétitives. À la fin, tu as transformé Claude en spécialiste de TES tâches grâce à Cowork et aux skills.',
                'lessons' => [
                    ['Le game changer 2026 : c\'est quoi Cowork', 480, "La grande nouveauté pour les non-devs. Différence avec le Claude classique. Ce que ça change concrètement (fichiers, dossiers, apps).\n\n▶ À toi : vérifie si ton ordi est compatible."],
                    ['Installer et configurer Cowork', 540, "Installation pas à pas. Première connexion, l'interface.\n\n▶ À toi : installe Cowork."],
                    ['Ta première automatisation sur tes fichiers', 600, "Laisser Claude lire/modifier tes fichiers locaux. Démo : organiser un dossier, traiter des fichiers. Permissions et sécurité.\n\n▶ À toi : fais ta première automatisation simple."],
                    ['C\'est quoi un Skill', 420, "Les skills expliqués simplement. Comment un skill transforme Claude en spécialiste. Exemples utiles.\n\n▶ À toi : identifie 3 skills utiles pour ton métier."],
                    ['Créer ton skill avec le Skill Creator', 600, "Utiliser le Skill Creator intégré. Démo : un skill métier de A à Z. Tester et ajuster.\n\n▶ À toi : crée ton premier skill."],
                    ['Les Plugins métier', 300, "C'est quoi un plugin (bundle de skills). Les plugins dispo (marketing, finance, ops...).\n\n▶ À toi : installe un plugin pertinent."],
                    ['On assemble : ton kit de skills perso', 300, "Construire sa boîte à outils. Transition vers l'automatisation complète.\n\n▶ À toi : liste les skills à créer pour ton activité."],
                ],
            ],
            [
                'title' => 'Ton équipe d\'agents qui bosse pour toi',
                'slug'  => 'equipe-agents',
                'description' => 'Et si Claude bossait pendant que tu dors ? À la fin, tu as connecté tes outils et créé des routines qui tournent toutes seules.',
                'lessons' => [
                    ['Connecter Claude à tes outils : c\'est quoi un Connector', 420, "Brancher Drive, Gmail, Slack, Notion. Pourquoi c'est puissant. Sécurité et permissions.\n\n▶ À toi : identifie les connecteurs utiles pour toi."],
                    ['Connecter Google Drive et Gmail', 540, "Démo : connecter Drive, puis Gmail. Cas d'usage concrets.\n\n▶ À toi : connecte tes premiers outils."],
                    ['Connecter Slack, Notion et autres', 480, "Démo : Slack et Notion. Combiner plusieurs connecteurs.\n\n▶ À toi : connecte un outil que tu utilises."],
                    ['Claude qui bosse seul : c\'est quoi une Routine', 480, "Les tâches récurrentes automatisées. Exemples de routines puissantes.\n\n▶ À toi : imagine 3 routines pour ton activité."],
                    ['Créer ta première routine', 600, "Démo : une routine de A à Z. Programmer et déclencher. Vérifier les résultats.\n\n▶ À toi : crée une routine simple."],
                    ['Cas d\'usage : reporting, veille, emails', 300, "Routine de reporting hebdo. Routine de veille concurrentielle. Routine de tri/réponse emails.\n\n▶ À toi : mets en place une routine pro utile."],
                    ['On assemble : piloter depuis ton mobile (Dispatch)', 300, "Lancer des tâches depuis ton téléphone. Récap du module.\n\n▶ À toi : teste Dispatch sur ton tel."],
                ],
            ],
            [
                'title' => 'Ton image de marque sans designer',
                'slug'  => 'image-marque',
                'description' => 'Tu n\'es pas designer mais tu as besoin de visuels pros. À la fin, tu crées ton identité, tes slides, tes maquettes — juste en discutant avec Claude Design.',
                'lessons' => [
                    ['Créer en discutant : c\'est quoi Claude Design', 480, "La nouveauté 2026 pour créer visuellement. Ce qu'on peut faire (decks, mockups, landing...). Pourquoi tu vas être en avance.\n\n▶ À toi : explore l'interface."],
                    ['Ton premier projet : un pitch deck', 600, "Démo : créer une présentation de A à Z. Décrire ce qu'on veut, affiner.\n\n▶ À toi : crée un deck sur ton sujet."],
                    ['Créer une landing page ou un mockup', 540, "Démo : générer une maquette. Itérer sur le design.\n\n▶ À toi : crée une maquette pour ton projet."],
                    ['Utiliser ton brand : design system extraction', 480, "Faire analyser ton site/brand existant. Récupérer couleurs, typos, style. Appliquer ton identité automatiquement.\n\n▶ À toi : extrais ton design system."],
                    ['Peaufiner visuellement : inline edits & sliders', 420, "Modifier visuellement, pas qu'en texte. Les adjustment sliders.\n\n▶ À toi : ajuste un de tes designs."],
                    ['On assemble : exporter vers Canva et Figma', 360, "Récupérer ton travail dans Canva / Figma. Finaliser ailleurs si besoin.\n\n▶ À toi : exporte un design et finalise-le."],
                ],
            ],
            [
                'title' => 'Lance ton produit : site + app',
                'slug'  => 'lance-ton-produit',
                'description' => 'Le grand final. À la fin, tu as construit et mis en ligne ton site web ET ta première application web — sans coder à la main.',
                'lessons' => [
                    ['La vision : Claude comme système, pas comme outil', 420, "Arrêter de voir Claude comme un chatbot. Penser en workflows et systèmes. La vision d'ensemble de tout ce qu'on a appris.\n\n▶ À toi : dessine ton système idéal."],
                    ['Construire ton site avec Cowork + Design', 720, "Démo complète : un site de A à Z. Combiner les outils appris. Mettre en ligne.\n\n▶ À toi : commence ton propre site."],
                    ['Ta première application web (déployée)', 720, "Démo : construire une app web simple. Sans coder à la main. Déployer et partager.\n\n▶ À toi : démarre ta première app."],
                    ['Le système complet : automatiser ton business', 540, "Combiner connectors + routines + skills. Démo : un système email + reporting + veille.\n\n▶ À toi : assemble ton système d'automatisation."],
                    ['Rester à jour : Claude évolue tous les mois', 300, "Suivre les sorties Anthropic. Le canal veille du Discord.\n\n▶ À toi : mets en place ta routine de veille."],
                    ['On assemble : ta feuille de route 90 jours', 300, "Récap de toute la formation. Ton plan d'action pour les 90 prochains jours. Continuer dans le Discord.\n\n▶ À toi : écris ton plan d'action et partage-le."],
                ],
            ],
        ];

        $lessonsByModule = [];
        foreach ($modules as $mIdx => $moduleSpec) {
            $moduleNumber = $mIdx + 1;
            $module = (new Module())
                ->setSlug($moduleSpec['slug'])
                ->setTitle($moduleSpec['title'])
                ->setDescription($moduleSpec['description'])
                ->setDisplayOrder($moduleNumber);
            $formation->addModule($module);
            $manager->persist($module);

            $lessons = [];
            foreach ($moduleSpec['lessons'] as $lIdx => [$title, $durationSeconds, $description]) {
                $lessonNumber = $lIdx + 1;
                $lesson = (new Lesson())
                    ->setSlug('m'.$moduleNumber.'-l'.$lessonNumber)
                    ->setTitle($title)
                    ->setVimeoVideoId('9999'.$moduleNumber.$lessonNumber)
                    ->setDescription($description)
                    ->setDurationSeconds($durationSeconds)
                    ->setDisplayOrder($lessonNumber);
                $module->addLesson($lesson);
                $manager->persist($lesson);
                $this->seedLessonResources($manager, $lesson, $moduleNumber, $lessonNumber);
                $lessons[] = $lesson;
            }
            $lessonsByModule[$mIdx] = $lessons;
        }

        return $lessonsByModule;
    }

    private function seedManusFormation(ObjectManager $manager): Formation
    {
        $manus = (new Formation())
            ->setSlug('manus-2026')
            ->setTitle('Formation Manus 2026')
            ->setSubtitle('L\'agent IA qui exécute pendant que tu fais autre chose.')
            ->setDescription('Découvre Manus, le co-pilote autonome qui transforme tes intentions en livrables. Bientôt disponible.')
            ->setPriceCents(34700)
            ->setCurrency('EUR')
            ->setPublished(true)
            ->setComingSoon(true)
            ->setDisplayOrder(2);
        $manager->persist($manus);

        $modulesSpec = [
            ['Découvrir Manus',          'decouvrir-manus',       'Comprendre l\'agent : ce qu\'il fait vraiment, ce qu\'il ne fait pas.'],
            ['Brief & garde-fous',       'brief-garde-fous',      'Lui parler comme à un junior brillant qu\'on ne supervise pas.'],
            ['Automatisations métier',   'automatisations-metier','Plug Manus dans tes outils — il livre, tu valides.'],
            ['Finale : ton bras droit',  'bras-droit',            'Ton workflow où Manus devient un membre de ton équipe.'],
        ];

        $this->seedModules($manager, $manus, $modulesSpec, lessonsPerModule: 3, vimeoPrefix: '7777');

        return $manus;
    }

    /**
     * @param list<array{0: string, 1: string, 2?: string}> $modulesSpec
     * @return array<int, array<int, Lesson>> indexed by module index, value = list of lessons
     */
    private function seedModules(ObjectManager $manager, Formation $formation, array $modulesSpec, int $lessonsPerModule, string $vimeoPrefix): array
    {
        $lessonsByModule = [];
        foreach ($modulesSpec as $i => $spec) {
            [$title, $slug] = $spec;
            $description = $spec[2] ?? 'Module '.($i + 1).' — '.$title;
            $module = (new Module())
                ->setSlug($slug)
                ->setTitle($title)
                ->setDescription($description)
                ->setDisplayOrder($i + 1);
            $formation->addModule($module);
            $manager->persist($module);

            $lessons = [];
            for ($li = 1; $li <= $lessonsPerModule; $li++) {
                $lesson = (new Lesson())
                    ->setSlug('m'.($i + 1).'-l'.$li)
                    ->setTitle('Leçon '.$li.' — '.$module->getTitle())
                    ->setVimeoVideoId($vimeoPrefix.($i + 1).$li)
                    ->setDescription('Contenu pédagogique de la leçon '.$li.'.')
                    ->setDurationSeconds(60 * (8 + $li * 2))
                    ->setDisplayOrder($li);
                $module->addLesson($lesson);
                $manager->persist($lesson);
                $this->seedLessonResources($manager, $lesson, $i + 1, $li);
                $lessons[] = $lesson;
            }
            $lessonsByModule[$i] = $lessons;
        }

        return $lessonsByModule;
    }

    private function seedLessonResources(ObjectManager $manager, Lesson $lesson, int $moduleIdx, int $lessonIdx): void
    {
        $pdf = (new Resource())
            ->setLesson($lesson)
            ->setType(ResourceType::File)
            ->setTitle('Fiche récap — Leçon '.$lessonIdx)
            ->setFilePath('/files/lessons/m'.$moduleIdx.'-l'.$lessonIdx.'.pdf')
            ->setDisplayOrder(1);
        $manager->persist($pdf);

        $link = (new Resource())
            ->setLesson($lesson)
            ->setType(ResourceType::Link)
            ->setTitle('Lien utile — Aller plus loin')
            ->setUrl('https://docs.anthropic.com/')
            ->setDisplayOrder(2);
        $manager->persist($link);
    }

    private function enroll(
        ObjectManager $manager,
        User $user,
        Formation $formation,
        EnrollmentSource $source,
        ?int $amountCents = null,
        ?string $stripeSessionId = null,
        ?string $stripePaymentIntentId = null,
    ): Enrollment {
        $enrollment = (new Enrollment())
            ->setUser($user)
            ->setFormation($formation)
            ->setSource($source)
            ->setAmountCents($amountCents)
            ->setStripeSessionId($stripeSessionId)
            ->setStripePaymentIntentId($stripePaymentIntentId);
        $manager->persist($enrollment);

        return $enrollment;
    }

    /**
     * @param list<Lesson> $lessons
     */
    private function markModuleCompleted(ObjectManager $manager, Enrollment $enrollment, array $lessons): void
    {
        foreach ($lessons as $lesson) {
            $this->markLessonCompleted($manager, $enrollment, $lesson);
        }
    }

    private function markLessonCompleted(ObjectManager $manager, Enrollment $enrollment, Lesson $lesson): void
    {
        $progress = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lesson);
        $progress->recordWatch($lesson->getDurationSeconds(), 100);
        $progress->markCompleted();
        $manager->persist($progress);
    }

    private function recordPartialWatch(ObjectManager $manager, Enrollment $enrollment, Lesson $lesson, int $percent): void
    {
        $progress = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lesson);
        $progress->recordWatch((int) round($lesson->getDurationSeconds() * $percent / 100), $percent);
        $manager->persist($progress);
    }

    /**
     * @param string[] $roles
     */
    private function makeUser(string $email, string $plainPassword, string $firstName, string $lastName, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles($roles);
        $user->setIsVerified(true);
        $user->setPassword($this->hasher->hashPassword($user, $plainPassword));

        return $user;
    }
}
