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
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,400;1,9..144,500&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body, body.ea-light {
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
                --sidebar-bg: #1F3025;
                --sidebar-border-color: #1F3025;
                --sidebar-logo-color: #FCFAF5;
                --sidebar-menu-color: rgba(252, 250, 245, 0.78);
                --sidebar-menu-icon-color: rgba(252, 250, 245, 0.55);
                --sidebar-menu-header-color: #E8587A;
                --sidebar-menu-active-item-bg: rgba(200, 57, 94, 0.22);
                --sidebar-menu-active-item-color: #FCFAF5;
                --bs-body-font-family: 'Manrope', system-ui, -apple-system, sans-serif;
            }
            body { background: var(--body-bg); }
            .main-header .logo, .app-logo, .navbar-brand { font-family: 'Fraunces', Georgia, serif !important; }
            .content-header h1, .content-header-title, h1.title, .ea-content h1 {
                font-family: 'Fraunces', Georgia, serif !important;
                font-weight: 500 !important;
                letter-spacing: -0.02em;
            }
        </style>
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
            ->setFaviconPath('favicon.ico');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addHtmlContentToHead(self::BRAND_HEAD);
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
        yield MenuItem::linkTo(ResourceCrudController::class, 'Ressources', 'fa fa-link');

        yield MenuItem::section('Progression');
        yield MenuItem::linkTo(LessonProgressCrudController::class, 'Progressions leçon', 'fa fa-chart-line');

        yield MenuItem::section();
        yield MenuItem::linkToRoute('Retour à l\'app', 'fa fa-arrow-left', 'app_dashboard');
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-sign-out-alt');
    }
}
