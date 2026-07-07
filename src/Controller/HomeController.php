<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function __invoke(): RedirectResponse
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        return match (true) {
            $this->isGranted('ROLE_ADMIN') => $this->redirectToRoute('app_admin_dashboard'),
            $this->isGranted('ROLE_MANAGER') => $this->redirectToRoute('app_manager_dashboard'),
            default => $this->redirectToRoute('app_guard_dashboard'),
        };
    }
}
