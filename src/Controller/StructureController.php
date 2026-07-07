<?php

namespace App\Controller;

use App\Entity\Cell;
use App\Form\CellType;
use App\Repository\AssignmentRepository;
use App\Repository\BuildingRepository;
use App\Repository\CellRepository;
use App\Repository\WingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/cells/new', name: 'app_cell_new', methods: ['GET', 'POST'])]
    public function newCell(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $cell = new Cell();
        $form = $this->createForm(CellType::class, $cell);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cell);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Cellule %s creee.', $cell->getNumber()));

            return $this->redirectToRoute('app_cell_show', ['id' => $cell->getId()]);
        }

        return $this->render('structure/cell_form.html.twig', [
            'form' => $form,
            'cell' => $cell,
        ]);
    }

    #[Route('/cells/{id}', name: 'app_cell_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showCell(Cell $cell, AssignmentRepository $assignmentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GUARD');

        return $this->render('structure/cell_show.html.twig', [
            'cell' => $cell,
            'activeAssignments' => $assignmentRepository->findActiveForCell($cell),
            'history' => $assignmentRepository->findHistoryForCell($cell),
        ]);
    }

    #[Route('/cells/{id}/edit', name: 'app_cell_edit', methods: ['GET', 'POST'])]
    public function editCell(Request $request, Cell $cell, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(CellType::class, $cell);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', sprintf('Cellule %s mise a jour.', $cell->getNumber()));

            return $this->redirectToRoute('app_cell_show', ['id' => $cell->getId()]);
        }

        return $this->render('structure/cell_form.html.twig', [
            'form' => $form,
            'cell' => $cell,
        ]);
    }

    #[Route('/cells/{id}/delete', name: 'app_cell_delete', methods: ['POST'])]
    public function deleteCell(Request $request, Cell $cell, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete-cell-'.$cell->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if (!$cell->getAssignments()->isEmpty() || !$cell->getIncidents()->isEmpty()) {
            $this->addFlash('error', 'Impossible de supprimer une cellule liee a des affectations ou des incidents.');

            return $this->redirectToRoute('app_cell_show', ['id' => $cell->getId()]);
        }

        $entityManager->remove($cell);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Cellule %s supprimee.', $cell->getNumber()));

        return $this->redirectToRoute('app_structure_cells');
    }
}
