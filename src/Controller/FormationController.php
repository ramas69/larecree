<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\EnrollmentRepository;
use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class FormationController extends AbstractController
{
    #[Route('/formations/{slug}', name: 'app_formation_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(string $slug, FormationRepository $formations, EnrollmentRepository $enrollments): Response
    {
        $formation = $formations->findBySlug($slug);
        if ($formation === null || !$formation->isPublished()) {
            throw new NotFoundHttpException('Formation introuvable.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $enrollment = $enrollments->findOneByUserAndFormation($user, $formation);

        $completedLessonIds = [];
        if ($enrollment !== null) {
            foreach ($enrollment->getProgresses() as $p) {
                if ($p->isCompleted() && $p->getLesson() !== null) {
                    $completedLessonIds[$p->getLesson()->getId()] = true;
                }
            }
        }

        return $this->render('formation/show.html.twig', [
            'formation'         => $formation,
            'enrollment'        => $enrollment,
            'completedLessonIds' => $completedLessonIds,
        ]);
    }
}
