<?php

namespace App\Controller;

use App\Entity\Building;
use App\Entity\Wing;
use App\Form\WingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WingController extends AbstractController
{
    #[Route('/buildings/{building}/wings/new', name: 'app_wing_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Building $building, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $wing = new Wing();
        $wing->setBuilding($building);

        $form = $this->createForm(WingType::class, $wing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($wing);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Aile %s creee.', $wing->getName()));

            return $this->redirectToRoute('app_building_show', ['id' => $building->getId()]);
        }

        return $this->render('structure/wing_form.html.twig', [
            'form' => $form,
            'wing' => $wing,
            'building' => $building,
        ]);
    }

    #[Route('/wings/{id}/edit', name: 'app_wing_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Wing $wing, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(WingType::class, $wing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', sprintf('Aile %s mise a jour.', $wing->getName()));

            return $this->redirectToRoute('app_building_show', ['id' => $wing->getBuilding()->getId()]);
        }

        return $this->render('structure/wing_form.html.twig', [
            'form' => $form,
            'wing' => $wing,
            'building' => $wing->getBuilding(),
        ]);
    }

    #[Route('/wings/{id}/delete', name: 'app_wing_delete', methods: ['POST'])]
    public function delete(Request $request, Wing $wing, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete-wing-'.$wing->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $building = $wing->getBuilding();

        if (!$wing->getCells()->isEmpty()) {
            $this->addFlash('error', 'Impossible de supprimer une aile qui contient des cellules.');

            return $this->redirectToRoute('app_building_show', ['id' => $building->getId()]);
        }

        $entityManager->remove($wing);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Aile %s supprimee.', $wing->getName()));

        return $this->redirectToRoute('app_building_show', ['id' => $building->getId()]);
    }
}
