<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\EnrollmentRepository;
use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CatalogueController extends AbstractController
{
    #[Route('/formations', name: 'app_catalogue')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(FormationRepository $formations, EnrollmentRepository $enrollments): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userEnrollments = $enrollments->findByUser($user);
        $enrolledFormationIds = [];
        foreach ($userEnrollments as $e) {
            $f = $e->getFormation();
            if ($f !== null) {
                $enrolledFormationIds[$f->getId()] = true;
            }
        }

        return $this->render('catalogue/index.html.twig', [
            'formations'          => $formations->findPublished(),
            'enrolledFormationIds' => $enrolledFormationIds,
        ]);
    }
}
