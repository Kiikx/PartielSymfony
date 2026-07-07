<?php

namespace App\Controller;

use App\Entity\Building;
use App\Form\BuildingType;
use App\Repository\BuildingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BuildingController extends AbstractController
{
    #[Route('/buildings', name: 'app_building_index', methods: ['GET'])]
    public function index(BuildingRepository $buildingRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        return $this->render('structure/building_index.html.twig', [
            'buildings' => $buildingRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/buildings/new', name: 'app_building_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $building = new Building();
        $form = $this->createForm(BuildingType::class, $building);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($building);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Batiment %s cree.', $building->getName()));

            return $this->redirectToRoute('app_building_show', ['id' => $building->getId()]);
        }

        return $this->render('structure/building_form.html.twig', [
            'form' => $form,
            'building' => $building,
        ]);
    }

    #[Route('/buildings/{id}', name: 'app_building_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Building $building): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        return $this->render('structure/building_show.html.twig', [
            'building' => $building,
        ]);
    }

    #[Route('/buildings/{id}/edit', name: 'app_building_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Building $building, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(BuildingType::class, $building);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', sprintf('Batiment %s mis a jour.', $building->getName()));

            return $this->redirectToRoute('app_building_show', ['id' => $building->getId()]);
        }

        return $this->render('structure/building_form.html.twig', [
            'form' => $form,
            'building' => $building,
        ]);
    }

    #[Route('/buildings/{id}/delete', name: 'app_building_delete', methods: ['POST'])]
    public function delete(Request $request, Building $building, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete-building-'.$building->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if (!$building->getWings()->isEmpty()) {
            $this->addFlash('error', 'Impossible de supprimer un batiment qui contient des ailes.');

            return $this->redirectToRoute('app_building_show', ['id' => $building->getId()]);
        }

        $entityManager->remove($building);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Batiment %s supprime.', $building->getName()));

        return $this->redirectToRoute('app_building_index');
    }
}
