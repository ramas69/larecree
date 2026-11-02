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
     * Polices Google + variables de thème EasyAdmin dans le design « école » du site
     * (vert forêt, moutarde pour les boutons et le surligneur, jamais en texte).
     */
    private const BRAND_HEAD = <<<'HTML'
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;1,6..72,400;1,6..72,500&family=Lexend:wght@300;400;500;600&display=swap" rel="stylesheet">
        <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/super-build/ckeditor.js"></script>
        <style>
            /* Variables thème EA — mêmes sélecteurs que le core, chargées après donc prioritaires */
            :root,
            [data-bs-theme=light] {
                --bs-primary: #1F3025;
                --bs-primary-rgb: 31, 48, 37;
                --bs-link-color: #1F3025;
                --bs-link-color-rgb: 31, 48, 37;
                --bs-link-hover-color: #2E4637;
                --highlight-color: #1F3025;
                --highlight-bg: rgba(227, 171, 31, 0.25);
                --button-primary-bg: #E3AB1F;
                --button-primary-border-color: #E3AB1F;
                --button-primary-color: #1F3025;
                --button-primary-hover-bg: #D19C17;
                --button-primary-hover-border-color: #D19C17;
                --button-primary-active-bg: #D19C17;
                --button-primary-active-border-color: #D19C17;
                --text-color: #3F4A42;
                --body-bg: #F7F6F3;
                --content-bg: #F7F6F3;
                --bs-body-bg: #F7F6F3;
                --sidebar-bg: #1F3025;
                --sidebar-border-color: #1F3025;
                --sidebar-logo-color: #FFFFFF;
                --sidebar-menu-color: rgba(243, 241, 234, 0.8);
                --sidebar-menu-icon-color: rgba(243, 241, 234, 0.55);
                --sidebar-menu-header-color: rgba(243, 241, 234, 0.55);
                --sidebar-menu-active-item-bg: rgba(227, 171, 31, 0.22);
                --sidebar-menu-active-item-color: #FFFFFF;
                --bs-body-font-family: 'Lexend', system-ui, -apple-system, sans-serif;
            }

            /* Base */
            body.ea { background: #F7F6F3; font-family: 'Lexend', system-ui, sans-serif; font-weight: 300; color: #3F4A42; }
            body.ea strong, body.ea b, body.ea th, body.ea label { font-weight: 500; }
            .content { background: #F7F6F3; }

            /* Barre de côté : le tableau vert */
            .sidebar { background: #1F3025 !important; border-color: rgba(243, 241, 234, 0.08) !important; }
            .sidebar .logo, .sidebar .logo a, .sidebar .logo span {
                color: #FFFFFF !important;
                font-family: 'Newsreader', Georgia, serif !important;
            }
            .menu .menu-item .menu-item-contents { color: rgba(243, 241, 234, 0.8) !important; }
            .menu .menu-item .menu-icon { color: rgba(243, 241, 234, 0.5) !important; }
            .menu .menu-header {
                color: rgba(243, 241, 234, 0.55) !important;
                font-weight: 500;
                letter-spacing: 0.14em;
                text-transform: uppercase;
            }
            .menu .menu-item:hover .menu-item-contents { color: #FFFFFF !important; }
            .menu .menu-item:hover .menu-icon { color: rgba(243, 241, 234, 0.85) !important; }
            .menu .menu-item.active .menu-item-contents {
                background: rgba(227, 171, 31, 0.22) !important;
                color: #FFFFFF !important;
                border-radius: 8px;
                box-shadow: inset 3px 0 0 #E3AB1F;
            }
            .menu .menu-item.active .menu-icon { color: #FFFFFF !important; }

            /* Titres en Newsreader italique, comme le site */
            .content-header-title,
            .content-header-title *,
            h1.title {
                font-family: 'Newsreader', Georgia, serif !important;
                font-style: italic;
                font-weight: 400 !important;
                color: #1F3025;
            }

            /* Boutons moutarde, liens verts soulignés de moutarde */
            .btn-primary {
                background: #E3AB1F !important;
                border-color: #E3AB1F !important;
                color: #1F3025 !important;
                font-weight: 500;
            }
            .btn-primary:hover, .btn-primary:focus {
                background: #D19C17 !important;
                border-color: #D19C17 !important;
                color: #1F3025 !important;
            }
            .btn { border-radius: 10px; }
            a { color: #1F3025; }
            a:hover { color: #2E4637; text-decoration-color: #E3AB1F; }
            .badge.badge-primary, .badge-primary { background: #E3AB1F !important; color: #1F3025 !important; }
            :focus-visible { outline: 3px solid #1F3025; outline-offset: 2px; }
            /* Tableaux : la colonne triée en vert et moutarde (EasyAdmin la met en bleu) */
            .datagrid th.sorted { box-shadow: inset 0 -3px 0 #E3AB1F; }
            .datagrid th.sorted a, .datagrid th.sorted .icon { color: #1F3025 !important; }
            .datagrid th a { color: #1F3025; }

            /* CKEditor — hauteur + intégration au design */
            .ck.ck-editor { width: 100%; }
            .ck-editor__editable, .ck.ck-content { min-height: 360px; }
            .ck.ck-editor__editable_inline { padding: 1rem 1.2rem; }
            .ck.ck-toolbar { border-radius: 8px 8px 0 0 !important; background: #F7F6F3 !important; }
            .ck.ck-editor__main > .ck-editor__editable { border-radius: 0 0 8px 8px !important; }
            .ck.ck-editor__editable.ck-focused { border-color: #1F3025 !important; box-shadow: 0 0 0 3px #DFE7DF !important; }
            .ck.ck-button.ck-on, .ck.ck-button:active { background: rgba(227, 171, 31, 0.25) !important; color: #1F3025 !important; }
            .ck-content h2 { font-family: 'Newsreader', Georgia, serif; }
            .ck-content h3 { font-family: 'Newsreader', Georgia, serif; }
            .ck-content a { color: #1F3025; text-decoration: underline; text-decoration-color: #E3AB1F; }
            /* textarea brut (avant init / fallback) plus haut */
            textarea.ckeditor { min-height: 320px; }
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

    // Aperçu lecteur (vidéo actuelle + fichier choisi) + auto-remplit la durée en minutes.
    private const VIDEO_DURATION_BODY = <<<'HTML'
        <script>
        (function () {
            function human(bytes) {
                if (bytes > 1073741824) { return (bytes / 1073741824).toFixed(2) + ' Go'; }
                if (bytes > 1048576) { return (bytes / 1048576).toFixed(1) + ' Mo'; }
                return Math.round(bytes / 1024) + ' Ko';
            }
            function previewBox(fileInput) {
                var b = document.getElementById('video-preview-box');
                if (!b) {
                    b = document.createElement('div');
                    b.id = 'video-preview-box';
                    b.style.cssText = 'margin:.8rem 0 0;padding:.8rem;border:1px solid #E6DDC9;border-radius:12px;background:#fff;';
                    fileInput.parentNode.appendChild(b);
                }
                return b;
            }
            function renderPreview(fileInput, src, caption) {
                var b = previewBox(fileInput);
                b.innerHTML = '';
                var cap = document.createElement('p');
                cap.style.cssText = 'margin:0 0 .5rem;font-size:.82rem;font-weight:500;color:#1F3025;';
                cap.textContent = caption;
                var v = document.createElement('video');
                v.controls = true;
                v.preload = 'metadata';
                v.style.cssText = 'width:100%;max-height:240px;border-radius:8px;background:#000;display:block;';
                v.src = src;
                b.appendChild(cap);
                b.appendChild(v);
            }
            function lessonId() {
                var m = location.search.match(/entityId=(\d+)/)
                     || location.pathname.match(/\/(\d+)\/edit/)
                     || location.pathname.match(/\/lesson\/(\d+)/);
                return m ? m[1] : null;
            }
            function bind() {
                var fileInput = document.querySelector('input[type="file"][name$="[videoUpload]"]');
                if (!fileInput || fileInput.dataset.previewBound) { return; }
                fileInput.dataset.previewBound = '1';

                // Aperçu de la vidéo déjà enregistrée
                var nameField = document.querySelector('input[name$="[videoFilename]"]');
                var id = lessonId();
                if (nameField && nameField.value.trim() && id) {
                    renderPreview(fileInput, '/admin/video-preview/' + id + '?t=' + Date.now(), '▶ Vidéo actuelle : ' + nameField.value.trim());
                }

                // Aperçu + durée au choix d'un fichier
                fileInput.addEventListener('change', function () {
                    var file = fileInput.files && fileInput.files[0];
                    if (!file) { return; }

                    var url = URL.createObjectURL(file);
                    renderPreview(fileInput, url, '✓ ' + file.name + ' (' + human(file.size) + ') — clique « Enregistrer » pour l\'envoyer.');

                    var durationField = document.querySelector('input[name$="[durationMinutes]"]');
                    var probe = document.createElement('video');
                    probe.preload = 'metadata';
                    probe.onloadedmetadata = function () {
                        if (isFinite(probe.duration) && probe.duration > 0) {
                            var mins = Math.max(1, Math.round(probe.duration / 60));
                            if (durationField) {
                                durationField.value = mins;
                                durationField.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                            var cap = document.querySelector('#video-preview-box p');
                            if (cap) { cap.textContent = '✓ ' + file.name + ' (' + human(file.size) + ' · ~' + mins + ' min) — clique « Enregistrer ».'; }
                        }
                    };
                    probe.src = url;
                });
            }
            bind();
            document.addEventListener('turbo:load', bind);
            document.addEventListener('turbo:render', bind);
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
            ->setTitle('<span style="font-family:Newsreader,Georgia,serif;font-weight:400;">la <em style="font-style:italic;">récrée</em> tech.</span>')
            ->setFaviconPath('/favicon.svg')
            ->setDefaultColorScheme(ColorScheme::LIGHT)
            ->disableDarkMode();
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addHtmlContentToHead(self::BRAND_HEAD)
            ->addHtmlContentToBody(self::CKEDITOR_BODY)
            ->addHtmlContentToBody(self::VIDEO_DURATION_BODY);
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
