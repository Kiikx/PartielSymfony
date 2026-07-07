<?php

namespace App\Controller;

use App\Entity\Incident;
use App\Repository\IncidentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IncidentController extends AbstractController
{
    #[Route('/incidents', name: 'app_incident_index', methods: ['GET'])]
    public function index(IncidentRepository $incidentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GUARD');

        return $this->render('incident/index.html.twig', [
            'incidents' => $incidentRepository->findBy([], ['occurredAt' => 'DESC'], 30),
            'stats' => [
                'open' => $incidentRepository->count(['status' => Incident::STATUS_OPEN]),
                'processing' => $incidentRepository->count(['status' => Incident::STATUS_PROCESSING]),
                'closed' => $incidentRepository->count(['status' => Incident::STATUS_CLOSED]),
                'critical' => $incidentRepository->count(['severity' => Incident::SEVERITY_CRITICAL]),
            ],
        ]);
    }
}
