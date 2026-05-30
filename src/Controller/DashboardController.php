<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dashboard\DashboardData;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(DashboardData $dashboardData): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $snapshot = $dashboardData->forUser($user);

        return $this->render('dashboard/index.html.twig', [
            'snapshot' => $snapshot,
        ]);
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->redirectToRoute('app_login');
    }
}
