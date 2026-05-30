<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Crée un utilisateur (admin, vip ou student) directement en base.',
)]
final class CreateUserCommand extends Command
{
    private const ROLE_MAP = [
        'admin'   => 'ROLE_ADMIN',
        'vip'     => 'ROLE_VIP',
        'student' => 'ROLE_STUDENT',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email (login)')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe en clair (sera hashé)')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'admin | vip | student', 'student')
            ->addOption('first', null, InputOption::VALUE_REQUIRED, 'Prénom', 'User')
            ->addOption('last', null, InputOption::VALUE_REQUIRED, 'Nom', '')
            ->addOption('replace', null, InputOption::VALUE_NONE, 'Remplacer l\'utilisateur existant avec le même email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email     = (string) $input->getArgument('email');
        $plainPwd  = (string) $input->getArgument('password');
        $roleKey   = strtolower((string) $input->getOption('role'));
        $firstName = (string) $input->getOption('first');
        $lastName  = (string) $input->getOption('last');
        $replace   = (bool) $input->getOption('replace');

        if (!isset(self::ROLE_MAP[$roleKey])) {
            $io->error(sprintf('Rôle invalide « %s ». Attendu : admin | vip | student.', $roleKey));
            return Command::INVALID;
        }

        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing !== null && !$replace) {
            $io->error(sprintf('Un utilisateur existe déjà avec l\'email %s. Ajoute --replace pour écraser.', $email));
            return Command::FAILURE;
        }

        $user = $existing ?? new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles([self::ROLE_MAP[$roleKey]]);
        $user->setIsVerified(true);
        $user->setPassword($this->hasher->hashPassword($user, $plainPwd));

        if ($existing === null) {
            $this->em->persist($user);
        }
        $this->em->flush();

        $io->success(sprintf(
            '%s utilisateur : %s (%s) %s.',
            $existing !== null ? 'Remplacé' : 'Créé',
            $email,
            self::ROLE_MAP[$roleKey],
            $firstName.' '.$lastName,
        ));

        return Command::SUCCESS;
    }
}
