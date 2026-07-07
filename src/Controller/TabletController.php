<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\GuardUser;
use App\Entity\Inmate;
use App\Entity\User;
use App\Form\ActivityQuickType;
use App\Form\ParticipationCheckInType;
use App\Repository\ActivityRepository;
use App\Repository\AssignmentRepository;
use App\Repository\IncidentRepository;
use App\Repository\InmateRepository;
use App\Service\ActivityParticipationService;
use Doctrine\ORM\EntityManagerInterface;
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
        ActivityRepository $activityRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_GUARD');

        $uid = trim((string) $request->query->get('uid', ''));
        $inmate = $uid !== '' ? $inmateRepository->findOneByUid($uid) : null;
        $todayActivities = $activityRepository->findScheduledForDay(new \DateTimeImmutable('today'));

        $user = $this->getUser();

        $activityForm = $this->createForm(ActivityQuickType::class, new Activity());
        $checkInForm = $inmate !== null && $todayActivities !== []
            ? $this->createForm(ParticipationCheckInType::class, ['inmateId' => $inmate->getId()], ['activities' => $todayActivities])
            : null;

        return $this->render('tablet/index.html.twig', [
            'searchedUid' => $uid,
            'inmate' => $inmate,
            'activeAssignment' => $inmate !== null ? $assignmentRepository->findActiveForInmate($inmate) : null,
            'assignedZone' => $user instanceof GuardUser ? $user->getAssignedZone() : null,
            'todayActivities' => $todayActivities,
            'activityForm' => $activityForm,
            'checkInForm' => $checkInForm,
            'openIncidents' => $incidentRepository->findRecent(limit: 6),
        ]);
    }

    #[Route('/guard/tablet/activities', name: 'app_tablet_activity_new', methods: ['POST'])]
    public function createActivity(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GUARD');

        $actor = $this->getUser();
        \assert($actor instanceof User);

        $activity = new Activity();
        $form = $this->createForm(ActivityQuickType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $activity->setCreatedBy($actor);
            $entityManager->persist($activity);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Activite "%s" ajoutee.', $activity->getLabel()));
        } else {
            $this->addFlash('error', 'Impossible de creer cette activite, verifiez les champs.');
        }

        return $this->redirectToRoute('app_guard_tablet', ['uid' => $request->query->get('uid', '')]);
    }

    #[Route('/guard/tablet/checkin', name: 'app_tablet_checkin', methods: ['POST'])]
    public function checkIn(
        Request $request,
        ActivityParticipationService $participationService,
        InmateRepository $inmateRepository,
        ActivityRepository $activityRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_GUARD');

        $actor = $this->getUser();
        \assert($actor instanceof User);

        $todayActivities = $activityRepository->findScheduledForDay(new \DateTimeImmutable('today'));
        $form = $this->createForm(ParticipationCheckInType::class, null, ['activities' => $todayActivities]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inmate = $inmateRepository->find($form->get('inmateId')->getData());

            if ($inmate instanceof Inmate) {
                $participationService->recordParticipation(
                    $form->get('activity')->getData(),
                    $inmate,
                    $form->get('status')->getData(),
                    $actor,
                );

                $this->addFlash('success', sprintf('Pointage enregistre pour %s.', $inmate->getFullName()));

                return $this->redirectToRoute('app_guard_tablet', ['uid' => $inmate->getUid()]);
            }
        }

        $this->addFlash('error', 'Impossible d\'enregistrer ce pointage, verifiez les champs.');

        return $this->redirectToRoute('app_guard_tablet');
    }
}
