<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Endpoint d'upload image pour CKEditor (SimpleUploadAdapter).
 * Réservé ROLE_ADMIN (déjà couvert par access_control ^/admin).
 */
#[IsGranted('ROLE_ADMIN')]
final class UploadImageController extends AbstractController
{
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
    private const MAX_BYTES = 5_000_000; // 5 Mo

    #[Route('/admin/upload-image', name: 'admin_upload_image', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('upload');
        if ($file === null) {
            return new JsonResponse(['error' => ['message' => 'Aucun fichier reçu.']], 400);
        }

        if (!in_array($file->getMimeType(), self::ALLOWED, true)) {
            return new JsonResponse(['error' => ['message' => 'Format non supporté (JPG, PNG, WEBP, GIF, SVG).']], 415);
        }
        if ($file->getSize() > self::MAX_BYTES) {
            return new JsonResponse(['error' => ['message' => 'Fichier trop lourd (max 5 Mo).']], 413);
        }

        $ext = $file->guessExtension() ?: 'bin';
        $name = bin2hex(random_bytes(8)).'-'.time().'.'.$ext;
        $destDir = $this->getParameter('kernel.project_dir').'/public/uploads/content';

        try {
            $file->move($destDir, $name);
        } catch (\Throwable) {
            return new JsonResponse(['error' => ['message' => 'Échec de l\'enregistrement.']], 500);
        }

        // Format de réponse attendu par CKEditor SimpleUploadAdapter.
        return new JsonResponse(['url' => '/uploads/content/'.$name]);
    }
}
