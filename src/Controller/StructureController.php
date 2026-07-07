<?php

namespace App\Controller;

use App\Entity\Cell;
use App\Repository\BuildingRepository;
use App\Repository\CellRepository;
use App\Repository\WingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StructureController extends AbstractController
{
    #[Route('/cells', name: 'app_structure_cells', methods: ['GET'])]
    public function cells(
        CellRepository $cellRepository,
        BuildingRepository $buildingRepository,
        WingRepository $wingRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_GUARD');

        return $this->render('structure/cells.html.twig', [
            'cells' => $cellRepository->findBy([], ['number' => 'ASC'], 40),
            'stats' => [
                'buildings' => $buildingRepository->count([]),
                'wings' => $wingRepository->count([]),
                'cells' => $cellRepository->count([]),
                'available' => $cellRepository->count(['status' => Cell::STATUS_AVAILABLE]),
                'full' => $cellRepository->count(['status' => Cell::STATUS_FULL]),
                'maintenance' => $cellRepository->count(['status' => Cell::STATUS_MAINTENANCE]),
            ],
        ]);
    }
}
