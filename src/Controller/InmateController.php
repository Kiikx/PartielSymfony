<?php

namespace App\Controller;

use App\Entity\Inmate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InmateController extends AbstractController
{
    #[Route('/inmates', name: 'app_inmate_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $databaseReady = true;

        try {
            $repository = $entityManager->getRepository(Inmate::class);
            $inmates = $repository->findBy([], ['arrivalDate' => 'DESC'], 30);
            $stats = [
                'total' => $repository->count([]),
                'incarcerated' => $repository->count(['status' => Inmate::STATUS_INCARCERATED]),
                'released' => $repository->count(['status' => Inmate::STATUS_RELEASED]),
                'externalTransfers' => $repository->count(['status' => Inmate::STATUS_EXTERNAL_TRANSFER]),
            ];
        } catch (\Throwable) {
            $databaseReady = false;
            $inmates = [];
            $stats = [
                'total' => 0,
                'incarcerated' => 0,
                'released' => 0,
                'externalTransfers' => 0,
            ];
        }

        return $this->render('inmate/index.html.twig', [
            'databaseReady' => $databaseReady,
            'inmates' => $inmates,
            'stats' => $stats,
        ]);
    }
}
