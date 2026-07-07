<?php

namespace App\Tests\Command;

use App\Command\CreateAdminUserCommand;
use App\Entity\AdminUser;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateAdminUserCommandTest extends TestCase
{
    public function testCreatesAdminUser(): void
    {
        $persistedUser = null;
        $command = new CreateAdminUserCommand(
            $this->createUserRepository(null),
            $this->createEntityManager($persistedUser),
            $this->createPasswordHasher('hashed-password', expectsHash: true),
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'email' => 'ADMIN@PAS.TEST',
            'password' => 'password123',
            'firstName' => 'David',
            'lastName' => 'Mgr',
            '--super-admin' => true,
            '--service' => 'MEP',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertInstanceOf(AdminUser::class, $persistedUser);
        self::assertSame('admin@pas.test', $persistedUser->getEmail());
        self::assertSame('David', $persistedUser->getFirstName());
        self::assertSame('Mgr', $persistedUser->getLastName());
        self::assertSame('MEP', $persistedUser->getService());
        self::assertTrue($persistedUser->isSuperAdmin());
        self::assertTrue($persistedUser->isActive());
        self::assertSame('hashed-password', $persistedUser->getPassword());
    }

    public function testRejectsExistingEmail(): void
    {
        $persistedUser = null;
        $command = new CreateAdminUserCommand(
            $this->createUserRepository(new AdminUser()),
            $this->createEntityManager($persistedUser, expectsPersist: false),
            $this->createPasswordHasher('hashed-password', expectsHash: false),
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'email' => 'admin@pas.test',
            'password' => 'password123',
            'firstName' => 'David',
            'lastName' => 'Mgr',
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertNull($persistedUser);
        self::assertStringContainsString('already exists', $tester->getDisplay());
    }

    private function createUserRepository(?User $existingUser): UserRepository
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'admin@pas.test'])
            ->willReturn($existingUser);

        return $userRepository;
    }

    private function createEntityManager(?User &$persistedUser, bool $expectsPersist = true): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($expectsPersist ? self::once() : self::never())
            ->method('persist')
            ->willReturnCallback(static function (User $user) use (&$persistedUser): void {
                $persistedUser = $user;
            });
        $entityManager
            ->expects($expectsPersist ? self::once() : self::never())
            ->method('flush');

        return $entityManager;
    }

    private function createPasswordHasher(string $hashedPassword, bool $expectsHash): UserPasswordHasherInterface
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher
            ->expects($expectsHash ? self::once() : self::never())
            ->method('hashPassword')
            ->willReturn($hashedPassword);

        return $passwordHasher;
    }
}
