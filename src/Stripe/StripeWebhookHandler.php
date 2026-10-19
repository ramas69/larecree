<?php

declare(strict_types=1);

namespace App\Stripe;

use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Webhook;

final class StripeWebhookHandler
{
    public function __construct(
        private readonly string $stripeWebhookSecret,
        private readonly StripeCheckoutService $checkoutService,
    ) {
    }

    /**
     * Vérifie la signature + dispatch.
     *
     * @throws \UnexpectedValueException si payload mal formé
     * @throws \Stripe\Exception\SignatureVerificationException si signature invalide
     */
    public function handle(string $payload, string $sigHeader): Event
    {
        $event = Webhook::constructEvent($payload, $sigHeader, $this->stripeWebhookSecret);

        // Paiement différé (Klarna possible) : completed arrive en 'unpaid', l'encaissement vient avec async_payment_succeeded.
        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            /** @var Session $session */
            $session = $event->data->object;
            if ($session->payment_status === 'paid') {
                $this->checkoutService->handleSessionCompleted($session);
            }
        }

        return $event;
    }
}
