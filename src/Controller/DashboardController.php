<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_dashboard', methods: ['GET'])]
    public function admin(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('dashboard/admin.html.twig');
    }

    #[Route('/manager', name: 'app_manager_dashboard', methods: ['GET'])]
    public function manager(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        return $this->render('dashboard/manager.html.twig');
    }

    #[Route('/guard', name: 'app_guard_dashboard', methods: ['GET'])]
    public function guard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GUARD');

        return $this->render('dashboard/guard.html.twig');
    }
}
