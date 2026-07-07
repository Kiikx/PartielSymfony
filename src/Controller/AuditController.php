<?php

namespace App\Controller;

use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuditController extends AbstractController
{
    #[Route('/audit', name: 'app_audit_index', methods: ['GET'])]
    public function index(Request $request, AuditLogRepository $auditLogRepository, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $filters = [
            'actor' => $request->query->get('actor'),
            'action' => $request->query->get('action'),
            'entityClass' => $request->query->get('entityClass'),
            'from' => $request->query->get('from'),
            'to' => $request->query->get('to'),
        ];

        return $this->render('audit/index.html.twig', [
            'auditLogs' => $auditLogRepository->search($filters),
            'actions' => $auditLogRepository->findDistinctActions(),
            'entityClasses' => $auditLogRepository->findDistinctEntityClasses(),
            'filters' => $filters,
            'users' => $userRepository->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC']),
        ]);
    }
}
