<?php

namespace App\Controller;

use App\Entity\Building;
use App\Entity\Cell;
use App\Entity\Wing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StructureController extends AbstractController
{
    #[Route('/cells/new', name: 'app_structure_cell_new', methods: ['GET'])]
    public function newCell(): Response
    {
        return $this->render('structure/cell_form.html.twig');
    }

    #[Route('/cells/{id<\d+>}/edit', name: 'app_structure_cell_edit', methods: ['GET'])]
    public function editCell(Cell $cell): Response
    {
        return $this->render('structure/cell_edit.html.twig', [
            'cell' => $cell,
        ]);
    }

    #[Route('/cells/{id<\d+>}', name: 'app_structure_cell_show', methods: ['GET'])]
    public function showCell(Cell $cell): Response
    {
        return $this->render('structure/cell_show.html.twig', [
            'cell' => $cell,
        ]);
    }

    #[Route('/cells', name: 'app_structure_cells')]
    public function cells(EntityManagerInterface $entityManager): Response
    {
        $databaseReady = true;

        try {
            $cellRepository = $entityManager->getRepository(Cell::class);
            $cells = $cellRepository->findBy([], ['number' => 'ASC'], 40);
            $stats = [
                'buildings' => $entityManager->getRepository(Building::class)->count([]),
                'wings' => $entityManager->getRepository(Wing::class)->count([]),
                'cells' => $cellRepository->count([]),
                'available' => $cellRepository->count(['status' => Cell::STATUS_AVAILABLE]),
                'full' => $cellRepository->count(['status' => Cell::STATUS_FULL]),
                'maintenance' => $cellRepository->count(['status' => Cell::STATUS_MAINTENANCE]),
            ];
        } catch (\Throwable) {
            $databaseReady = false;
            $cells = [];
            $stats = [
                'buildings' => 0,
                'wings' => 0,
                'cells' => 0,
                'available' => 0,
                'full' => 0,
                'maintenance' => 0,
            ];
        }

        return $this->render('structure/cells.html.twig', [
            'databaseReady' => $databaseReady,
            'cells' => $cells,
            'stats' => $stats,
        ]);
    }
}
