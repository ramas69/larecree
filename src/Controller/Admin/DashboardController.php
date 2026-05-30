<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\EnrollmentSource;
use App\Repository\EnrollmentRepository;
use App\Repository\PaymentRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
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
            ->setTitle('La Récrée Tech — Admin')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
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
