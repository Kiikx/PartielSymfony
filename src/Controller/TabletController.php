<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\GuardUser;
use App\Repository\AssignmentRepository;
use App\Repository\IncidentRepository;
use App\Repository\InmateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TabletController extends AbstractController
{
    #[Route('/guard/tablet', name: 'app_guard_tablet', methods: ['GET'])]
    #[Route('/tablet', name: 'app_tablet_guard', methods: ['GET'])]
    public function index(
        Request $request,
        InmateRepository $inmateRepository,
        AssignmentRepository $assignmentRepository,
        IncidentRepository $incidentRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_GUARD');

        $uid = trim((string) $request->query->get('uid', ''));
        $inmate = $uid !== '' ? $inmateRepository->findOneByUid($uid) : null;

        $user = $this->getUser();

        return $this->render('tablet/index.html.twig', [
            'searchedUid' => $uid,
            'inmate' => $inmate,
            'activeAssignment' => $inmate !== null ? $assignmentRepository->findActiveForInmate($inmate) : null,
            'assignedZone' => $user instanceof GuardUser ? $user->getAssignedZone() : null,
            'activityTypes' => Activity::TYPES,
            'openIncidents' => $incidentRepository->findRecent(limit: 6),
        ]);
    }
}
