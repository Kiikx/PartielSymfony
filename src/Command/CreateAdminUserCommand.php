<?php

namespace App\Command;

use App\Entity\AdminUser;
use App\Entity\User;
use App\Repository\UserRepository;
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
    name: 'app:create-admin-user',
    description: 'Creates an administrator account for production deployments.',
)]
final class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Administrator email.')
            ->addArgument('password', InputArgument::REQUIRED, 'Administrator password.')
            ->addArgument('firstName', InputArgument::REQUIRED, 'Administrator first name.')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Administrator last name.')
            ->addOption('service', null, InputOption::VALUE_REQUIRED, 'Administrator service name.', 'Direction')
            ->addOption('super-admin', null, InputOption::VALUE_NONE, 'Marks the account as a super administrator.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = mb_strtolower($this->readStringArgument($input, 'email'));
        $password = $this->readStringArgument($input, 'password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('The email address is invalid.');

            return Command::INVALID;
        }

        if (mb_strlen($password) < 8) {
            $io->error('The password must contain at least 8 characters.');

            return Command::INVALID;
        }

        if ($this->userRepository->findOneBy(['email' => $email]) instanceof User) {
            $io->error(sprintf('A user already exists with email "%s".', $email));

            return Command::FAILURE;
        }

        $adminUser = new AdminUser();
        $adminUser
            ->setEmail($email)
            ->setFirstName($this->readStringArgument($input, 'firstName'))
            ->setLastName($this->readStringArgument($input, 'lastName'))
            ->setIsActive(true);
        $adminUser
            ->setService($this->readNullableStringOption($input, 'service'))
            ->setSuperAdmin((bool) $input->getOption('super-admin'));
        $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, $password));

        $this->entityManager->persist($adminUser);
        $this->entityManager->flush();

        $io->success(sprintf('Administrator "%s" created.', $adminUser->getEmail()));

        return Command::SUCCESS;
    }

    private function readStringArgument(InputInterface $input, string $name): string
    {
        $value = $input->getArgument($name);
        \assert(is_string($value));

        return trim($value);
    }

    private function readNullableStringOption(InputInterface $input, string $name): ?string
    {
        $value = $input->getOption($name);
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
