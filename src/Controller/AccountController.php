<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\EnrollmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class AccountController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_account', methods: ['GET'])]
    public function index(EnrollmentRepository $enrollments): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/index.html.twig', [
            'user'        => $user,
            'enrollments' => $enrollments->findByUser($user),
        ]);
    }

    #[Route('/mon-compte/profil', name: 'app_account_profile', methods: ['POST'])]
    public function updateProfile(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('account_profile', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF invalide.');
        }

        $first = trim((string) $request->request->get('first_name'));
        $last  = trim((string) $request->request->get('last_name'));
        $email = trim((string) $request->request->get('email'));

        if ($first === '' || $email === '') {
            $this->addFlash('error', 'Le prénom et l\'email sont obligatoires.');

            return $this->redirectToRoute('app_account');
        }

        // Email déjà pris par un autre compte ?
        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing !== null && $existing->getId() !== $user->getId()) {
            $this->addFlash('error', 'Cet email est déjà utilisé par un autre compte.');

            return $this->redirectToRoute('app_account');
        }

        $user->setFirstName($first);
        $user->setLastName($last);
        $user->setEmail($email);
        $em->flush();

        $this->addFlash('success', 'Profil mis à jour.');

        return $this->redirectToRoute('app_account');
    }

    #[Route('/mon-compte/mot-de-passe', name: 'app_account_password', methods: ['POST'])]
    public function updatePassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('account_password', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF invalide.');
        }

        $current = (string) $request->request->get('current_password');
        $new     = (string) $request->request->get('new_password');
        $confirm = (string) $request->request->get('new_password_confirm');

        if (!$hasher->isPasswordValid($user, $current)) {
            $this->addFlash('error', 'Mot de passe actuel incorrect.');

            return $this->redirectToRoute('app_account');
        }
        if (strlen($new) < 8) {
            $this->addFlash('error', 'Le nouveau mot de passe doit faire au moins 8 caractères.');

            return $this->redirectToRoute('app_account');
        }
        if ($new !== $confirm) {
            $this->addFlash('error', 'Les deux mots de passe ne correspondent pas.');

            return $this->redirectToRoute('app_account');
        }

        $user->setPassword($hasher->hashPassword($user, $new));
        $em->flush();

        $this->addFlash('success', 'Mot de passe mis à jour.');

        return $this->redirectToRoute('app_account');
    }
}
