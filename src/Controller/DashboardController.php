<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\AuditLog;
use App\Entity\Building;
use App\Entity\Cell;
use App\Entity\Incident;
use App\Entity\Inmate;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/planning', name: 'app_planning_index', methods: ['GET'])]
    public function planning(EntityManagerInterface $entityManager): Response
    {
        return $this->render('dashboard/module.html.twig', [
            'title' => 'Planning',
            'eyebrow' => 'Activites',
            'description' => 'Activites planifiees depuis la base.',
            'headers' => ['Activite', 'Type', 'Lieu', 'Date'],
            'rows' => array_map(static fn (Activity $activity): array => [
                $activity->getLabel(),
                $activity->getType(),
                $activity->getLocation() ?: '-',
                $activity->getScheduledAt()->format('d/m/Y H:i'),
            ], $entityManager->getRepository(Activity::class)->findBy([], ['scheduledAt' => 'ASC'], 30)),
        ]);
    }

    #[Route('/reports', name: 'app_report_index', methods: ['GET'])]
    public function reports(EntityManagerInterface $entityManager): Response
    {
        return $this->render('dashboard/report.html.twig', [
            'inmates' => $entityManager->getRepository(Inmate::class)->count([]),
            'cells' => $entityManager->getRepository(Cell::class)->count([]),
            'incidents' => $entityManager->getRepository(Incident::class)->count([]),
            'criticalIncidents' => $entityManager->getRepository(Incident::class)->count(['severity' => Incident::SEVERITY_CRITICAL]),
        ]);
    }

    #[Route('/cameras', name: 'app_camera_index', methods: ['GET'])]
    public function cameras(): Response
    {
        return $this->render('dashboard/cameras.html.twig');
    }

    #[Route('/audit', name: 'app_audit_index', methods: ['GET'])]
    public function audit(EntityManagerInterface $entityManager): Response
    {
        return $this->render('dashboard/module.html.twig', [
            'title' => 'Audit',
            'eyebrow' => 'Traces applicatives',
            'description' => 'Dernieres actions sensibles enregistrees.',
            'headers' => ['Action', 'Entite', 'ID', 'Date'],
            'rows' => array_map(static fn (AuditLog $log): array => [
                $log->getAction(),
                basename(str_replace('\\', '/', $log->getEntityClass())),
                (string) ($log->getEntityId() ?: '-'),
                $log->getCreatedAt()->format('d/m/Y H:i'),
            ], $entityManager->getRepository(AuditLog::class)->findBy([], ['createdAt' => 'DESC'], 40)),
        ]);
    }

    #[Route('/exports/inmates', name: 'app_inmate_export', methods: ['GET'])]
    public function inmateExport(EntityManagerInterface $entityManager): Response
    {
        $inmates = $entityManager->getRepository(Inmate::class)->findBy([], ['arrivalDate' => 'DESC'], 50);

        return $this->render('dashboard/module.html.twig', [
            'title' => 'Export detenus',
            'eyebrow' => 'Extraction',
            'description' => 'Apercu des dossiers qui seront exportes.',
            'headers' => ['UID', 'Nom', 'Statut', 'Arrivee'],
            'rows' => array_map(static fn (Inmate $inmate): array => [
                $inmate->getUid(),
                $inmate->getFullName(),
                $inmate->getStatus(),
                $inmate->getArrivalDate()?->format('d/m/Y') ?: '-',
            ], $inmates),
        ]);
    }

    #[Route('/buildings', name: 'app_building_index', methods: ['GET'])]
    public function buildings(EntityManagerInterface $entityManager): Response
    {
        return $this->render('dashboard/module.html.twig', [
            'title' => 'Batiments',
            'eyebrow' => 'Structure',
            'description' => 'Referentiel des batiments presents en base.',
            'headers' => ['Code', 'Nom', 'Adresse', 'Ailes'],
            'rows' => array_map(static fn (Building $building): array => [
                $building->getCode(),
                $building->getName(),
                $building->getAddress() ?: '-',
                (string) $building->getWings()->count(),
            ], $entityManager->getRepository(Building::class)->findBy([], ['code' => 'ASC'])),
        ]);
    }
}
