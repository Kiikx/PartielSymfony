<?php

namespace App\Controller;

use App\Entity\Incident;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IncidentController extends AbstractController
{
    #[Route('/incidents', name: 'app_incident_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $databaseReady = true;

        try {
            $repository = $entityManager->getRepository(Incident::class);
            $incidents = $repository->findBy([], ['occurredAt' => 'DESC'], 30);
            $stats = [
                'open' => $repository->count(['status' => Incident::STATUS_OPEN]),
                'processing' => $repository->count(['status' => Incident::STATUS_PROCESSING]),
                'closed' => $repository->count(['status' => Incident::STATUS_CLOSED]),
                'critical' => $repository->count(['severity' => Incident::SEVERITY_CRITICAL]),
            ];
        } catch (\Throwable) {
            $databaseReady = false;
            $incidents = [];
            $stats = [
                'open' => 0,
                'processing' => 0,
                'closed' => 0,
                'critical' => 0,
            ];
        }

        return $this->render('incident/index.html.twig', [
            'databaseReady' => $databaseReady,
            'incidents' => $incidents,
            'stats' => $stats,
        ]);
    }
}
