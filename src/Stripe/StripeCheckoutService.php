<?php

declare(strict_types=1);

namespace App\Stripe;

use App\Entity\Enrollment;
use App\Entity\EnrollmentSource;
use App\Entity\Formation;
use App\Entity\Payment;
use App\Entity\PaymentStatus;
use App\Entity\User;
use App\Repository\PaymentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session as StripeSession;
use Stripe\StripeClient;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class StripeCheckoutService
{
    private const SENDER_EMAIL = 'hello@larecreetech.com';
    private const SENDER_NAME  = 'La Récrée Tech';

    public function __construct(
        private readonly string $stripeSecretKey,
        private readonly EntityManagerInterface $em,
        private readonly PaymentRepository $payments,
        private readonly UserRepository $users,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function client(): StripeClient
    {
        return new StripeClient($this->stripeSecretKey);
    }

    /**
     * Crée une Stripe Checkout Session pour la formation donnée.
     * Persiste un Payment en pending pour audit.
     */
    public function createSession(Formation $formation): StripeSession
    {
        $successUrl = $this->urlGenerator->generate(
            'app_checkout_success',
            ['session_id' => '{CHECKOUT_SESSION_ID}'],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $cancelUrl = $this->urlGenerator->generate('app_checkout_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $session = $this->client()->checkout->sessions->create([
            'mode'                 => 'payment',
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl,
            'customer_creation'    => 'always',
            'billing_address_collection' => 'auto',
            'allow_promotion_codes' => true,
            'metadata' => [
                'formation_slug' => $formation->getSlug(),
                'formation_id'   => (string) $formation->getId(),
            ],
            'line_items' => [[
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => strtolower($formation->getCurrency()),
                    'unit_amount'  => $formation->getPriceCents(),
                    'product_data' => [
                        'name'        => $formation->getTitle(),
                        'description' => $formation->getSubtitle() ?? '',
                    ],
                ],
            ]],
        ]);

        $payment = (new Payment())
            ->setFormation($formation)
            ->setStripeSessionId($session->id)
            ->setAmountCents($formation->getPriceCents())
            ->setCurrency(strtoupper($formation->getCurrency()));
        $this->em->persist($payment);
        $this->em->flush();

        return $session;
    }

    /**
     * Traite l'événement checkout.session.completed.
     *  - Marque Payment succeeded
     *  - Find/create User
     *  - Generate reset-password token
     *  - Send welcome email
     *  - Create Enrollment Stripe
     */
    public function handleSessionCompleted(StripeSession $session): void
    {
        $payment = $this->payments->findOneByStripeSessionId($session->id);
        if ($payment === null) {
            return;
        }
        if ($payment->getStatus() === PaymentStatus::Succeeded) {
            return;
        }

        $formation = $payment->getFormation();
        if ($formation === null) {
            $payment->setStatus(PaymentStatus::Failed);
            $this->em->flush();

            return;
        }

        $customerEmail = $session->customer_details?->email ?? $session->customer_email ?? null;
        $payment->setCustomerEmail($customerEmail);
        $payment->setStripePaymentIntentId(is_string($session->payment_intent) ? $session->payment_intent : null);

        if ($customerEmail === null) {
            $payment->setStatus(PaymentStatus::Failed);
            $this->em->flush();

            return;
        }

        $user = $this->users->findOneBy(['email' => $customerEmail]);
        if ($user === null) {
            $user = new User();
            $user->setEmail($customerEmail);
            $user->setFirstName($this->guessFirstNameFromSession($session));
            $user->setLastName('');
            $user->setRoles(['ROLE_STUDENT']);
            $user->setIsVerified(true);
            $user->setPassword(bin2hex(random_bytes(16))); // placeholder (will be reset)
            $this->em->persist($user);
        }

        $resetToken = bin2hex(random_bytes(32));
        $user->setResetPasswordToken($resetToken);
        $user->setResetPasswordExpiresAt(new \DateTimeImmutable('+24 hours'));

        $existingEnrollment = $this->findEnrollment($user, $formation);
        if ($existingEnrollment === null) {
            $enrollment = (new Enrollment())
                ->setUser($user)
                ->setFormation($formation)
                ->setSource(EnrollmentSource::Stripe)
                ->setAmountCents($payment->getAmountCents())
                ->setStripeSessionId($session->id)
                ->setStripePaymentIntentId($payment->getStripePaymentIntentId());
            $this->em->persist($enrollment);
        }

        $payment->setUser($user);
        $payment->setStatus(PaymentStatus::Succeeded);
        $this->em->flush();

        $this->sendWelcomeEmail($user, $formation, $resetToken);
    }

    private function findEnrollment(User $user, Formation $formation): ?Enrollment
    {
        foreach ($user->getEnrollments() as $e) {
            if ($e->getFormation()?->getId() === $formation->getId()) {
                return $e;
            }
        }

        return null;
    }

    private function guessFirstNameFromSession(StripeSession $session): string
    {
        $name = $session->customer_details?->name;
        if ($name === null || $name === '') {
            return 'Récréen·ne';
        }
        $parts = explode(' ', trim($name));

        return $parts[0];
    }

    private function sendWelcomeEmail(User $user, Formation $formation, string $resetToken): void
    {
        $setupUrl = $this->urlGenerator->generate(
            'app_reset_password_confirm',
            ['token' => $resetToken],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = (new TemplatedEmail())
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to($user->getEmail())
            ->subject('Bienvenue à La Récrée Tech — fixe ton mot de passe')
            ->htmlTemplate('emails/welcome_after_checkout.html.twig')
            ->context([
                'user'      => $user,
                'formation' => $formation,
                'setupUrl'  => $setupUrl,
            ]);

        $this->mailer->send($email);
    }
}
