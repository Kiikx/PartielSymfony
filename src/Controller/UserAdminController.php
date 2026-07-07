<?php

namespace App\Controller;

use App\Entity\AdminUser;
use App\Entity\Building;
use App\Entity\GuardUser;
use App\Entity\ManagerUser;
use App\Entity\User;
use App\Entity\Wing;
use App\Form\UserAdminType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UserAdminController extends AbstractController
{
    #[Route('/users', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC']);

        return $this->render('user/index.html.twig', [
            'stats' => $this->createStats($users),
            'userRows' => $this->createUserRows($users),
        ]);
    }

    #[Route('/users/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(UserAdminType::class, [
            'profileType' => UserAdminType::PROFILE_GUARD,
            'isActive' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $this->getFormData($form->getData());
            if ($this->emailIsAlreadyUsed($userRepository, $data['email'])) {
                $form->get('email')->addError(new FormError('Cette adresse email est deja utilisee.'));
            } else {
                $user = $this->createUser($data['profileType']);
                $this->applyData($user, $data);
                $user->setPassword($passwordHasher->hashPassword($user, $data['plainPassword']));

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', sprintf('Compte %s cree.', $user->getEmail()));

                return $this->redirectToRoute('app_user_index');
            }
        }

        return $this->render('user/form.html.twig', [
            'form' => $form,
            'user' => null,
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(UserAdminType::class, $this->createInitialData($user), [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $this->getFormData($form->getData());
            $data['profileType'] = $this->getProfileType($user);
            if ($this->emailIsAlreadyUsed($userRepository, $data['email'], $user)) {
                $form->get('email')->addError(new FormError('Cette adresse email est deja utilisee.'));
            } else {
                $this->applyData($user, $data);
                if ($data['plainPassword'] !== '') {
                    $user->setPassword($passwordHasher->hashPassword($user, $data['plainPassword']));
                }

                $entityManager->flush();

                $this->addFlash('success', sprintf('Compte %s mis a jour.', $user->getEmail()));

                return $this->redirectToRoute('app_user_index');
            }
        }

        return $this->render('user/form.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}/toggle', name: 'app_user_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('toggle-user-'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Impossible de desactiver votre propre compte.');

            return $this->redirectToRoute('app_user_index');
        }

        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

        $this->addFlash('success', sprintf('Compte %s %s.', $user->getEmail(), $user->isActive() ? 'active' : 'desactive'));

        return $this->redirectToRoute('app_user_index');
    }

    /**
     * @param list<User> $users
     *
     * @return array{total: int, active: int, admins: int, managers: int, guards: int}
     */
    private function createStats(array $users): array
    {
        return [
            'total' => count($users),
            'active' => count(array_filter($users, static fn (User $user): bool => $user->isActive())),
            'admins' => count(array_filter($users, static fn (User $user): bool => $user instanceof AdminUser)),
            'managers' => count(array_filter($users, static fn (User $user): bool => $user instanceof ManagerUser)),
            'guards' => count(array_filter($users, static fn (User $user): bool => $user instanceof GuardUser)),
        ];
    }

    /**
     * @param list<User> $users
     *
     * @return list<array{user: User, profileLabel: string, attachmentLabel: string}>
     */
    private function createUserRows(array $users): array
    {
        return array_map(fn (User $user): array => [
            'user' => $user,
            'profileLabel' => $this->getProfileLabel($user),
            'attachmentLabel' => $this->getAttachmentLabel($user),
        ], $users);
    }

    private function createUser(string $profileType): User
    {
        return match ($profileType) {
            UserAdminType::PROFILE_ADMIN => new AdminUser(),
            UserAdminType::PROFILE_MANAGER => new ManagerUser(),
            default => new GuardUser(),
        };
    }

    private function emailIsAlreadyUsed(UserRepository $userRepository, string $email, ?User $currentUser = null): bool
    {
        $existingUser = $userRepository->findOneBy(['email' => mb_strtolower(trim($email))]);

        return $existingUser instanceof User && $existingUser !== $currentUser;
    }

    /**
     * @return array<string, mixed>
     */
    private function createInitialData(User $user): array
    {
        return [
            'profileType' => $this->getProfileType($user),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'plainPassword' => '',
            'isActive' => $user->isActive(),
            'service' => $user instanceof AdminUser ? $user->getService() : null,
            'superAdmin' => $user instanceof AdminUser && $user->isSuperAdmin(),
            'managedBuilding' => $user instanceof ManagerUser ? $user->getManagedBuilding() : null,
            'badgeNumber' => $user instanceof GuardUser ? $user->getBadgeNumber() : '',
            'assignedZone' => $user instanceof GuardUser ? $user->getAssignedZone() : null,
        ];
    }

    private function getProfileType(User $user): string
    {
        return match (true) {
            $user instanceof AdminUser => UserAdminType::PROFILE_ADMIN,
            $user instanceof ManagerUser => UserAdminType::PROFILE_MANAGER,
            default => UserAdminType::PROFILE_GUARD,
        };
    }

    private function getProfileLabel(User $user): string
    {
        return match (true) {
            $user instanceof AdminUser => 'Administrateur',
            $user instanceof ManagerUser => 'Manager',
            default => 'Surveillant',
        };
    }

    private function getAttachmentLabel(User $user): string
    {
        if ($user instanceof AdminUser) {
            return $user->getService() ?: 'Direction';
        }

        if ($user instanceof ManagerUser) {
            return $user->getManagedBuilding()?->getName() ?? '-';
        }

        if ($user instanceof GuardUser) {
            return $user->getAssignedZone()?->getName() ?? '-';
        }

        return '-';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyData(User $user, array $data): void
    {
        $user
            ->setEmail($data['email'])
            ->setFirstName($data['firstName'])
            ->setLastName($data['lastName'])
            ->setIsActive((bool) $data['isActive']);

        if ($user instanceof AdminUser) {
            $user
                ->setService($this->nullableString($data['service']))
                ->setSuperAdmin((bool) $data['superAdmin']);
        }

        if ($user instanceof ManagerUser) {
            $user->setManagedBuilding($data['managedBuilding'] instanceof Building ? $data['managedBuilding'] : null);
        }

        if ($user instanceof GuardUser) {
            $user
                ->setBadgeNumber($this->requiredBadgeNumber($data['badgeNumber']))
                ->setAssignedZone($data['assignedZone'] instanceof Wing ? $data['assignedZone'] : null);
        }
    }

    /**
     * @param mixed $data
     *
     * @return array{profileType: string, email: string, firstName: string, lastName: string, plainPassword: string, isActive: bool, service: mixed, superAdmin: bool, managedBuilding: mixed, badgeNumber: mixed, assignedZone: mixed}
     */
    private function getFormData(mixed $data): array
    {
        \assert(is_array($data));

        return [
            'profileType' => is_string($data['profileType'] ?? null) ? $data['profileType'] : UserAdminType::PROFILE_GUARD,
            'email' => is_string($data['email'] ?? null) ? $data['email'] : '',
            'firstName' => is_string($data['firstName'] ?? null) ? $data['firstName'] : '',
            'lastName' => is_string($data['lastName'] ?? null) ? $data['lastName'] : '',
            'plainPassword' => is_string($data['plainPassword'] ?? null) ? $data['plainPassword'] : '',
            'isActive' => (bool) ($data['isActive'] ?? false),
            'service' => $data['service'] ?? null,
            'superAdmin' => (bool) ($data['superAdmin'] ?? false),
            'managedBuilding' => $data['managedBuilding'] ?? null,
            'badgeNumber' => $data['badgeNumber'] ?? null,
            'assignedZone' => $data['assignedZone'] ?? null,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function requiredBadgeNumber(mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            return 'PAS-G-'.mb_strtoupper(bin2hex(random_bytes(3)));
        }

        return $value;
    }
}
