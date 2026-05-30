<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\Module;
use App\Entity\User;
use App\Repository\EnrollmentRepository;
use App\Repository\FormationRepository;
use App\Repository\LessonProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class LessonController extends AbstractController
{
    #[Route('/formations/{slug}/{moduleSlug}/{lessonSlug}', name: 'app_lesson_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(
        string $slug,
        string $moduleSlug,
        string $lessonSlug,
        FormationRepository $formations,
        EnrollmentRepository $enrollments,
        LessonProgressRepository $progresses,
    ): Response {
        [$formation, $module, $lesson, $enrollment] = $this->resolveContext($slug, $moduleSlug, $lessonSlug, $formations, $enrollments);

        $progress = $progresses->findOneByEnrollmentAndLesson($enrollment, $lesson);
        $allLessonsInModule = $module->getLessons()->toArray();
        [$prevLesson, $nextLesson] = $this->resolveSiblings($formation, $module, $lesson);

        return $this->render('lesson/show.html.twig', [
            'formation'          => $formation,
            'module'             => $module,
            'lesson'             => $lesson,
            'enrollment'         => $enrollment,
            'progress'           => $progress,
            'allLessonsInModule' => $allLessonsInModule,
            'prevLesson'         => $prevLesson,
            'nextLesson'         => $nextLesson,
        ]);
    }

    #[Route('/formations/{slug}/{moduleSlug}/{lessonSlug}/complete', name: 'app_lesson_complete', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function markCompleted(
        string $slug,
        string $moduleSlug,
        string $lessonSlug,
        Request $request,
        FormationRepository $formations,
        EnrollmentRepository $enrollments,
        LessonProgressRepository $progresses,
        EntityManagerInterface $em,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('lesson_complete_'.$lessonSlug, (string) $request->request->get('_token'))) {
            throw new AccessDeniedHttpException('CSRF token invalide.');
        }

        [, , $lesson, $enrollment] = $this->resolveContext($slug, $moduleSlug, $lessonSlug, $formations, $enrollments);

        $progress = $progresses->findOneByEnrollmentAndLesson($enrollment, $lesson);
        if ($progress === null) {
            $progress = (new LessonProgress())->setEnrollment($enrollment)->setLesson($lesson);
            $em->persist($progress);
        }
        $progress->recordWatch($lesson->getDurationSeconds(), 100);
        $progress->markCompleted();
        $em->flush();

        $this->addFlash('success', 'Leçon « '.$lesson->getTitle().' » marquée comme terminée.');

        return $this->redirectToRoute('app_lesson_show', [
            'slug'       => $slug,
            'moduleSlug' => $moduleSlug,
            'lessonSlug' => $lessonSlug,
        ]);
    }

    /**
     * @return array{0: \App\Entity\Formation, 1: Module, 2: Lesson, 3: Enrollment}
     */
    private function resolveContext(
        string $slug,
        string $moduleSlug,
        string $lessonSlug,
        FormationRepository $formations,
        EnrollmentRepository $enrollments,
    ): array {
        $formation = $formations->findBySlug($slug);
        if ($formation === null || !$formation->isPublished()) {
            throw new NotFoundHttpException('Formation introuvable.');
        }

        $module = null;
        foreach ($formation->getModules() as $m) {
            if ($m->getSlug() === $moduleSlug) {
                $module = $m;
                break;
            }
        }
        if ($module === null) {
            throw new NotFoundHttpException('Module introuvable.');
        }

        $lesson = null;
        foreach ($module->getLessons() as $l) {
            if ($l->getSlug() === $lessonSlug) {
                $lesson = $l;
                break;
            }
        }
        if ($lesson === null) {
            throw new NotFoundHttpException('Leçon introuvable.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $enrollment = $enrollments->findOneByUserAndFormation($user, $formation);
        if ($enrollment === null) {
            throw new AccessDeniedHttpException('Tu dois être inscrit·e à la formation pour accéder à cette leçon.');
        }

        return [$formation, $module, $lesson, $enrollment];
    }

    /**
     * @return array{0: ?Lesson, 1: ?Lesson}
     */
    private function resolveSiblings(\App\Entity\Formation $formation, Module $currentModule, Lesson $currentLesson): array
    {
        $orderedLessons = [];
        foreach ($formation->getModules() as $m) {
            foreach ($m->getLessons() as $l) {
                $orderedLessons[] = [$m, $l];
            }
        }
        $prev = null;
        $next = null;
        $foundIndex = null;
        foreach ($orderedLessons as $i => [, $l]) {
            if ($l === $currentLesson) {
                $foundIndex = $i;
                break;
            }
        }
        if ($foundIndex !== null) {
            $prev = $orderedLessons[$foundIndex - 1][1] ?? null;
            $next = $orderedLessons[$foundIndex + 1][1] ?? null;
        }

        return [$prev, $next];
    }
}
