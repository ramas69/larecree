<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\FormationRepository;
use App\Repository\PaymentRepository;
use App\Stripe\StripeCheckoutService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CheckoutController extends AbstractController
{
    #[Route('/checkout/{slug}', name: 'app_checkout_start', methods: ['GET'])]
    public function start(string $slug, FormationRepository $formations, StripeCheckoutService $checkout): RedirectResponse
    {
        $formation = $formations->findBySlug($slug);
        if ($formation === null || !$formation->isPublished() || $formation->isComingSoon()) {
            throw new NotFoundHttpException('Formation introuvable.');
        }

        $session = $checkout->createSession($formation);

        return $this->redirect($session->url);
    }

    #[Route('/checkout/success', name: 'app_checkout_success', methods: ['GET'])]
    public function success(Request $request, PaymentRepository $payments): Response
    {
        $sessionId = (string) $request->query->get('session_id');
        $payment = $sessionId !== '' ? $payments->findOneByStripeSessionId($sessionId) : null;

        return $this->render('checkout/success.html.twig', [
            'payment'   => $payment,
            'formation' => $payment?->getFormation(),
        ]);
    }

    #[Route('/checkout/cancel', name: 'app_checkout_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        return $this->render('checkout/cancel.html.twig');
    }
}
