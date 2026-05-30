<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ResetPasswordHelper
{
    private const TOKEN_TTL_MINUTES = 60;
    private const SENDER_EMAIL = 'hello@larecreetech.com';
    private const SENDER_NAME = 'La Récrée Tech';

    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Génère un token, le persiste, et envoie l'email. Renvoie toujours true (pas de leak).
     */
    public function requestReset(string $email): void
    {
        $user = $this->users->findOneBy(['email' => $email]);
        if ($user === null) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $user->setResetPasswordToken($token);
        $user->setResetPasswordExpiresAt(new \DateTimeImmutable('+'.self::TOKEN_TTL_MINUTES.' minutes'));
        $this->em->flush();

        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password_confirm',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = (new TemplatedEmail())
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to($user->getEmail())
            ->subject('Réinitialise ton mot de passe — La Récrée Tech')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'user'     => $user,
                'resetUrl' => $resetUrl,
                'ttlMin'   => self::TOKEN_TTL_MINUTES,
            ]);

        $this->mailer->send($email);
    }

    public function findUserByToken(string $token): ?User
    {
        return $this->users->findOneByValidResetToken($token);
    }

    public function resetPasswordWith(User $user, string $newPlainPassword): void
    {
        $user->setPassword($this->hasher->hashPassword($user, $newPlainPassword));
        $user->setResetPasswordToken(null);
        $user->setResetPasswordExpiresAt(null);
        $this->em->flush();
    }
}
