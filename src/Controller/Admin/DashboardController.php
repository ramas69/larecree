<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\EnrollmentSource;
use App\Repository\EnrollmentRepository;
use App\Repository\PaymentRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ColorScheme;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    /**
     * Polices Google + override des variables de thème EasyAdmin façon DA La Récrée Tech.
     */
    private const BRAND_HEAD = <<<'HTML'
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,400;1,9..144,500&family=Manrope:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
        <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/super-build/ckeditor.js"></script>
        <style>
            /* Variables thème EA — mêmes sélecteurs que le core, chargées après donc prioritaires */
            :root,
            [data-bs-theme=light] {
                --bs-primary: #C8395E;
                --bs-primary-rgb: 200, 57, 94;
                --bs-link-color: #C8395E;
                --bs-link-color-rgb: 200, 57, 94;
                --bs-link-hover-color: #A82248;
                --highlight-color: #C8395E;
                --highlight-bg: rgba(200, 57, 94, 0.12);
                --button-primary-bg: #C8395E;
                --button-primary-border-color: #C8395E;
                --button-primary-color: #FCFAF5;
                --button-primary-hover-bg: #A82248;
                --button-primary-hover-border-color: #A82248;
                --button-primary-active-bg: #A82248;
                --button-primary-active-border-color: #A82248;
                --text-color: #14110D;
                --body-bg: #FCFAF5;
                --content-bg: #FCFAF5;
                --bs-body-bg: #FCFAF5;
                --sidebar-bg: #1F3025;
                --sidebar-border-color: #1F3025;
                --sidebar-logo-color: #FCFAF5;
                --sidebar-menu-color: rgba(252, 250, 245, 0.78);
                --sidebar-menu-icon-color: rgba(252, 250, 245, 0.55);
                --sidebar-menu-header-color: #E8587A;
                --sidebar-menu-active-item-bg: rgba(200, 57, 94, 0.25);
                --sidebar-menu-active-item-color: #FCFAF5;
                --bs-body-font-family: 'Manrope', system-ui, -apple-system, sans-serif;
            }

            /* Base */
            body.ea { background: #FCFAF5; font-family: 'Manrope', system-ui, sans-serif; }
            .content { background: #FCFAF5; }

            /* Sidebar tableau noir */
            .sidebar { background: #1F3025 !important; border-color: rgba(252, 250, 245, 0.08) !important; }
            .sidebar .logo, .sidebar .logo a, .sidebar .logo span {
                color: #FCFAF5 !important;
                font-family: 'Fraunces', Georgia, serif !important;
            }
            .menu .menu-item .menu-item-contents { color: rgba(252, 250, 245, 0.78) !important; }
            .menu .menu-item .menu-icon { color: rgba(252, 250, 245, 0.5) !important; }
            .menu .menu-header {
                color: #E8587A !important;
                font-family: 'DM Mono', monospace;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            }
            .menu .menu-item:hover .menu-item-contents { color: #FCFAF5 !important; }
            .menu .menu-item:hover .menu-icon { color: rgba(252, 250, 245, 0.85) !important; }
            .menu .menu-item.active .menu-item-contents {
                background: rgba(200, 57, 94, 0.28) !important;
                color: #FCFAF5 !important;
                border-radius: 8px;
            }
            .menu .menu-item.active .menu-icon { color: #E8587A !important; }

            /* Titres en Fraunces */
            .content-header-title,
            .content-header-title *,
            h1.title {
                font-family: 'Fraunces', Georgia, serif !important;
                font-weight: 500 !important;
                letter-spacing: -0.02em;
                color: #14110D;
            }

            /* Boutons + liens rose framboise */
            .btn-primary {
                background: #C8395E !important;
                border-color: #C8395E !important;
                color: #FCFAF5 !important;
            }
            .btn-primary:hover, .btn-primary:focus {
                background: #A82248 !important;
                border-color: #A82248 !important;
            }
            a { color: #C8395E; }
            a:hover { color: #A82248; }
            .badge.badge-primary, .badge-primary { background: #C8395E !important; color: #FCFAF5 !important; }
        </style>
        HTML;

    /**
     * CKEditor 5 (super-build CDN, global CKEDITOR) initialisé sur les textarea.ckeditor.
     * Toolbar riche : titres, couleurs texte/fond, alignement, listes, liens, images
     * (upload via /admin/upload-image), tableaux, citations. Licence GPL (gratuit).
     */
    private const CKEDITOR_BODY = <<<'HTML'
        <script>
        (function () {
            var CONFIG = {
                toolbar: {
                    items: [
                        'undo', 'redo', '|',
                        'heading', '|',
                        'fontColor', 'fontBackgroundColor', '|',
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'alignment', '|',
                        'bulletedList', 'numberedList', '|',
                        'outdent', 'indent', '|',
                        'link', 'blockQuote', 'insertImage', 'insertTable', 'mediaEmbed', '|',
                        'removeFormat'
                    ],
                    shouldNotGroupWhenFull: true
                },
                alignment: { options: ['left', 'center', 'right', 'justify'] },
                simpleUpload: { uploadUrl: '/admin/upload-image' },
                removePlugins: [
                    'RealTimeCollaborativeEditing', 'RealTimeCollaborativeComments',
                    'RealTimeCollaborativeRevisionHistory', 'RealTimeCollaborativeTrackChanges',
                    'PresenceList', 'Comments', 'CommentsRepository', 'TrackChanges', 'TrackChangesData',
                    'RevisionHistory', 'RevisionHistoryAdapter', 'Pagination', 'WProofreader', 'MathType',
                    'CKBox', 'CKBoxUtils', 'CKBoxImageEdit', 'CKFinder', 'CKFinderUploadAdapter',
                    'EasyImage', 'CloudServices', 'CloudServicesCommentsAdapter', 'ExportPdf', 'ExportWord',
                    'DocumentOutline', 'DocumentOutlineUI', 'TableOfContents', 'FormatPainter',
                    'SlashCommand', 'Template', 'PasteFromOfficeEnhanced', 'CaseChange',
                    'MultiLevelList', 'AIAssistant', 'Mermaid', 'Pagination', 'PaginationData'
                ],
                image: {
                    toolbar: ['imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|', 'toggleImageCaption', 'imageTextAlternative', '|', 'resizeImage']
                },
                table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
                language: 'fr'
            };
            var tries = 0;
            function initCKEditors() {
                var Editor = (window.CKEDITOR && window.CKEDITOR.ClassicEditor) || window.ClassicEditor;
                if (!Editor) {
                    if (tries++ > 40) { console.error('[cke] CKEDITOR introuvable après 6s — CDN bloqué ?'); return; }
                    return window.setTimeout(initCKEditors, 150);
                }
                var nodes = document.querySelectorAll('textarea.ckeditor:not([data-cke-ready])');
                console.log('[cke] editor prêt, textareas trouvés =', nodes.length);
                nodes.forEach(function (el) {
                    el.setAttribute('data-cke-ready', '1');
                    Editor.create(el, CONFIG).then(function () {
                        console.log('[cke] OK', el.id);
                    }).catch(function (err) {
                        console.error('[cke] config custom KO, fallback:', err && err.message ? err.message : err);
                        Editor.create(el, { removePlugins: CONFIG.removePlugins }).catch(function (e2) { console.error('[cke] fallback KO:', e2); });
                    });
                });
            }
            initCKEditors();
            // EasyAdmin navigue via Turbo : ré-initialiser après chaque navigation.
            document.addEventListener('turbo:load', initCKEditors);
            document.addEventListener('turbo:render', initCKEditors);
        })();
        </script>
        HTML;

    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly EnrollmentRepository $enrollments,
        private readonly UserRepository $users,
    ) {
    }

    public function index(): Response
    {
        $now        = new \DateTimeImmutable();
        $monthStart = $now->modify('first day of this month')->setTime(0, 0);
        $prevStart  = $monthStart->modify('-1 month');

        $revenueMonth = $this->payments->revenueBetweenCents($monthStart, $now);
        $revenuePrev  = $this->payments->revenueBetweenCents($prevStart, $monthStart);
        $revenueTotal = $this->payments->totalRevenueCents();
        $sales        = $this->payments->countSales();

        return $this->render('admin/dashboard.html.twig', [
            'revenueMonth'  => $revenueMonth,
            'revenuePrev'   => $revenuePrev,
            'revenueDelta'  => $revenuePrev > 0 ? (int) round(($revenueMonth - $revenuePrev) / $revenuePrev * 100) : null,
            'revenueTotal'  => $revenueTotal,
            'refundedTotal' => $this->payments->refundedTotalCents(),
            'sales'         => $sales,
            'avgOrder'      => $sales > 0 ? (int) round($revenueTotal / $sales) : 0,
            'vipCount'      => $this->enrollments->countBySource(EnrollmentSource::Vip),
            'usersCount'    => $this->users->count([]),
            'byFormation'   => $this->payments->revenueByFormation(),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<span style="font-family:Fraunces,Georgia,serif;font-weight:500;">la <em style="font-style:italic;color:#E8587A;">récrée</em> tech<span style="color:#C8395E;">.</span></span>')
            ->setFaviconPath('/favicon.svg')
            ->setDefaultColorScheme(ColorScheme::LIGHT)
            ->disableDarkMode();
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addHtmlContentToHead(self::BRAND_HEAD)
            ->addHtmlContentToBody(self::CKEDITOR_BODY);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Accueil — métriques', 'fa fa-chart-pie');

        yield MenuItem::section('Comptes');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
        yield MenuItem::linkTo(EnrollmentCrudController::class, 'Inscriptions', 'fa fa-ticket');

        yield MenuItem::section('Contenu');
        yield MenuItem::linkTo(FormationCrudController::class, 'Formations', 'fa fa-graduation-cap');
        yield MenuItem::linkTo(ModuleCrudController::class, 'Modules', 'fa fa-folder');
        yield MenuItem::linkTo(LessonCrudController::class, 'Leçons', 'fa fa-play-circle');

        yield MenuItem::section('Progression');
        yield MenuItem::linkTo(LessonProgressCrudController::class, 'Progressions leçon', 'fa fa-chart-line');

        yield MenuItem::section();
        yield MenuItem::linkToRoute('Retour à l\'app', 'fa fa-arrow-left', 'app_dashboard');
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-sign-out-alt');
    }
}
