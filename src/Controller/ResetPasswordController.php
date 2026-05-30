<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\ResetPasswordHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password', name: 'app_reset_password_request', methods: ['GET', 'POST'])]
    public function request(Request $request, ResetPasswordHelper $helper): Response
    {
        $sent = false;
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_request', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('CSRF invalide.');
            }
            $email = trim((string) $request->request->get('email'));
            if ($email !== '') {
                $helper->requestReset($email);
            }
            $sent = true;
        }

        return $this->render('security/reset_password_request.html.twig', [
            'sent' => $sent,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password_confirm', methods: ['GET', 'POST'])]
    public function confirm(string $token, Request $request, ResetPasswordHelper $helper): Response
    {
        $user = $helper->findUserByToken($token);
        if ($user === null) {
            return $this->render('security/reset_password_invalid.html.twig', [], new Response('', 410));
        }

        $errors = [];
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_confirm_'.$token, (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('CSRF invalide.');
            }
            $new     = (string) $request->request->get('password');
            $confirm = (string) $request->request->get('password_confirm');

            if (strlen($new) < 8) {
                $errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
            }
            if ($new !== $confirm) {
                $errors[] = 'Les deux mots de passe ne correspondent pas.';
            }

            if ($errors === []) {
                $helper->resetPasswordWith($user, $new);
                $this->addFlash('success', 'Mot de passe mis à jour. Tu peux te connecter.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password_confirm.html.twig', [
            'token'  => $token,
            'user'   => $user,
            'errors' => $errors,
        ]);
    }
}
