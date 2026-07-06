<?php

namespace App\Controller;

use App\Entity\ManagerUser;
use App\Service\Dashboard\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    #[Route('/admin', name: 'app_admin_dashboard', methods: ['GET'])]
    public function admin(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('dashboard/admin.html.twig', [
            'summary' => $this->dashboardService->buildSummary(),
        ]);
    }

    #[Route('/manager', name: 'app_manager_dashboard', methods: ['GET'])]
    public function manager(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $user = $this->getUser();
        $building = $user instanceof ManagerUser ? $user->getManagedBuilding() : null;

        return $this->render('dashboard/manager.html.twig', [
            'summary' => $this->dashboardService->buildSummary($building),
            'building' => $building,
        ]);
    }

    #[Route('/guard', name: 'app_guard_dashboard', methods: ['GET'])]
    public function guard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GUARD');

        return $this->render('dashboard/guard.html.twig', [
            'summary' => $this->dashboardService->buildSummary(),
        ]);
    }
}
