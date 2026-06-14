<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\LessonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Aperçu admin d'une vidéo auto-hébergée (réservé ROLE_ADMIN, sans contrôle d'inscription).
 */
#[IsGranted('ROLE_ADMIN')]
final class VideoPreviewController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('/admin/video-preview/{id}', name: 'admin_lesson_video_preview', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function preview(int $id, LessonRepository $lessons): BinaryFileResponse
    {
        $lesson = $lessons->find($id);
        if ($lesson === null) {
            throw new NotFoundHttpException('Leçon introuvable.');
        }

        $filename = $lesson->getVideoFilename();
        if ($filename === null || $filename === '') {
            throw new NotFoundHttpException('Aucune vidéo auto-hébergée pour cette leçon.');
        }

        $filename = basename($filename);
        $path = $this->projectDir.'/private/videos/'.$filename;
        if (!is_file($path)) {
            throw new NotFoundHttpException('Fichier vidéo introuvable sur le serveur.');
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'webm'  => 'video/webm',
            'ogg', 'ogv' => 'video/ogg',
            'mov'   => 'video/quicktime',
            'm4v'   => 'video/x-m4v',
            default => 'video/mp4',
        });
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename);
        $response->setPrivate();

        return $response;
    }
}
