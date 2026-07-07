<?php

namespace App\Controller;

use App\Entity\Cell;
use App\Entity\Incident;
use App\Entity\User;
use App\Form\IncidentReportType;
use App\Form\IncidentStatusType;
use App\Repository\CellRepository;
use App\Repository\IncidentRepository;
use App\Security\Voter\IncidentVoter;
use App\Service\IncidentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IncidentController extends AbstractController
{
    #[Route('/incidents', name: 'app_incident_index', methods: ['GET'])]
    public function index(Request $request, IncidentRepository $incidentRepository, CellRepository $cellRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GUARD');
        $viewer = $this->getUser();
        \assert($viewer instanceof User);

        $filters = [
            'text' => $request->query->get('text'),
            'status' => $request->query->get('status'),
            'severity' => $request->query->get('severity'),
            'cell' => $request->query->get('cell'),
        ];

        return $this->render('incident/index.html.twig', [
            'incidents' => $incidentRepository->searchForUser($viewer, $filters),
            'filters' => $filters,
            'cells' => $cellRepository->findBy([], ['number' => 'ASC']),
            'statuses' => Incident::STATUSES,
            'severities' => Incident::SEVERITIES,
            'stats' => [
                'total' => $incidentRepository->countForUser($viewer),
                'open' => $incidentRepository->countForUser($viewer, ['status' => Incident::STATUS_OPEN]),
                'processing' => $incidentRepository->countForUser($viewer, ['status' => Incident::STATUS_PROCESSING]),
                'critical' => $incidentRepository->countForUser($viewer, ['severity' => Incident::SEVERITY_CRITICAL]),
            ],
        ]);
    }

    #[Route('/incidents/new', name: 'app_incident_new', methods: ['GET', 'POST'])]
    public function new(Request $request, IncidentService $incidentService): Response
    {
        $this->denyAccessUnlessGranted(IncidentVoter::CREATE);

        $form = $this->createForm(IncidentReportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $actor = $this->getUser();
            \assert($actor instanceof User);

            $incident = $incidentService->create(
                $form->get('title')->getData(),
                $form->get('description')->getData(),
                $form->get('severity')->getData(),
                $actor,
                $this->resolveCell($form->get('cell')->getData()),
                $this->resolveOccurredAt($form->get('occurredAt')->getData()),
                $form->get('inmates')->getData(),
            );

            $this->addFlash('success', 'Incident signale et journalise.');

            return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
        }

        return $this->render('incident/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/incidents/{id}', name: 'app_incident_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Incident $incident): Response
    {
        $this->denyAccessUnlessGranted(IncidentVoter::VIEW, $incident);

        return $this->render('incident/show.html.twig', [
            'incident' => $incident,
        ]);
    }

    #[Route('/incidents/{id}/process', name: 'app_incident_process', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function process(Request $request, Incident $incident, IncidentService $incidentService): Response
    {
        $this->denyAccessUnlessGranted(IncidentVoter::EDIT, $incident);

        $statuses = $this->isGranted(IncidentVoter::CLOSE, $incident)
            ? [Incident::STATUS_OPEN, Incident::STATUS_PROCESSING, Incident::STATUS_CLOSED]
            : [Incident::STATUS_OPEN, Incident::STATUS_PROCESSING];

        $form = $this->createForm(IncidentStatusType::class, [
            'status' => $incident->getStatus(),
        ], [
            'statuses' => $statuses,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $status = $form->get('status')->getData();
            if ($status === Incident::STATUS_CLOSED) {
                $this->denyAccessUnlessGranted(IncidentVoter::CLOSE, $incident);
            }

            $actor = $this->getUser();
            \assert($actor instanceof User);

            $incidentService->updateStatus($incident, $status, $actor);
            $this->addFlash('success', 'Statut incident mis a jour.');

            return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
        }

        return $this->render('incident/process.html.twig', [
            'form' => $form,
            'incident' => $incident,
        ]);
    }

    private function resolveCell(mixed $value): ?Cell
    {
        return $value instanceof Cell ? $value : null;
    }

    private function resolveOccurredAt(mixed $value): ?\DateTimeImmutable
    {
        if (!$value instanceof \DateTimeInterface) {
            return null;
        }

        return \DateTimeImmutable::createFromInterface($value);
    }
}
