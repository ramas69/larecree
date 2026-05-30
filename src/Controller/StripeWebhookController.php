<?php

declare(strict_types=1);

namespace App\Controller;

use App\Stripe\StripeWebhookHandler;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController extends AbstractController
{
    #[Route('/webhook/stripe', name: 'app_webhook_stripe', methods: ['POST'])]
    public function handle(Request $request, StripeWebhookHandler $handler, LoggerInterface $logger): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = (string) $request->headers->get('Stripe-Signature');

        if ($sigHeader === '') {
            return new JsonResponse(['error' => 'Missing Stripe-Signature header'], 400);
        }

        try {
            $event = $handler->handle($payload, $sigHeader);
        } catch (\UnexpectedValueException $e) {
            $logger->warning('Stripe webhook invalid payload', ['err' => $e->getMessage()]);

            return new JsonResponse(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            $logger->warning('Stripe webhook signature mismatch', ['err' => $e->getMessage()]);

            return new JsonResponse(['error' => 'Invalid signature'], 400);
        }

        return new JsonResponse([
            'received' => true,
            'type'     => $event->type,
            'id'       => $event->id,
        ]);
    }
}
