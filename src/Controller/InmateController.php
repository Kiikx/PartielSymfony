<?php

namespace App\Controller;

use App\Entity\Inmate;
use App\Repository\InmateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InmateController extends AbstractController
{
    #[Route('/inmates', name: 'app_inmate_index', methods: ['GET'])]
    public function index(InmateRepository $inmateRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GUARD');

        return $this->render('inmate/index.html.twig', [
            'inmates' => $inmateRepository->findBy([], ['arrivalDate' => 'DESC'], 30),
            'stats' => [
                'total' => $inmateRepository->count([]),
                'incarcerated' => $inmateRepository->count(['status' => Inmate::STATUS_INCARCERATED]),
                'released' => $inmateRepository->count(['status' => Inmate::STATUS_RELEASED]),
                'externalTransfers' => $inmateRepository->count(['status' => Inmate::STATUS_EXTERNAL_TRANSFER]),
            ],
        ]);
    }
}
