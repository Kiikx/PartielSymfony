<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Incident;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TabletController extends AbstractController
{
    #[Route('/tablet', name: 'app_tablet_guard')]
    public function inmatePortal(EntityManagerInterface $entityManager): Response
    {
        $databaseReady = true;

        try {
            $todayActivities = $this->findTodayActivities($entityManager);
            $openIncidents = $entityManager->getRepository(Incident::class)->findBy(
                ['status' => Incident::STATUS_OPEN],
                ['occurredAt' => 'DESC'],
                6
            );
        } catch (\Throwable) {
            $databaseReady = false;
            $todayActivities = [];
            $openIncidents = [];
        }

        return $this->render('tablet/guard.html.twig', [
            'databaseReady' => $databaseReady,
            'todayActivities' => $todayActivities,
        ]);
    }

    /**
     * @return list<Activity>
     */
    private function findTodayActivities(EntityManagerInterface $entityManager): array
    {
        $start = new \DateTimeImmutable('today');
        $end = $start->modify('+1 day');

        return $entityManager->createQueryBuilder()
            ->select('activity')
            ->from(Activity::class, 'activity')
            ->where('activity.scheduledAt >= :start')
            ->andWhere('activity.scheduledAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('activity.scheduledAt', 'ASC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();
    }
}
