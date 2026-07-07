<?php

namespace App\Controller;

use App\Entity\Inmate;
use App\Form\InmateType;
use App\Repository\InmateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InmateController extends AbstractController
{
    #[Route('/inmates', name: 'app_inmate_index', methods: ['GET'])]
    public function index(Request $request, InmateRepository $inmateRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GUARD');

        $filters = [
            'uid' => $request->query->get('uid'),
            'status' => $request->query->get('status'),
            'securityLevel' => $request->query->get('securityLevel'),
        ];

        return $this->render('inmate/index.html.twig', [
            'inmates' => $inmateRepository->search($filters['uid'], $filters['status'], $filters['securityLevel']),
            'filters' => $filters,
            'statuses' => Inmate::STATUSES,
            'securityLevels' => Inmate::SECURITY_LEVELS,
            'stats' => [
                'total' => $inmateRepository->count([]),
                'incarcerated' => $inmateRepository->count(['status' => Inmate::STATUS_INCARCERATED]),
                'released' => $inmateRepository->count(['status' => Inmate::STATUS_RELEASED]),
                'externalTransfers' => $inmateRepository->count(['status' => Inmate::STATUS_EXTERNAL_TRANSFER]),
            ],
        ]);
    }

    #[Route('/inmates/new', name: 'app_inmate_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $inmate = new Inmate();
        $form = $this->createForm(InmateType::class, $inmate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($inmate);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Dossier detenu %s cree.', $inmate->getUid()));

            return $this->redirectToRoute('app_inmate_show', ['id' => $inmate->getId()]);
        }

        return $this->render('inmate/form.html.twig', [
            'form' => $form,
            'inmate' => $inmate,
        ]);
    }

    #[Route('/inmates/{id}', name: 'app_inmate_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Inmate $inmate): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GUARD');

        return $this->render('inmate/show.html.twig', [
            'inmate' => $inmate,
        ]);
    }

    #[Route('/inmates/{id}/edit', name: 'app_inmate_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Inmate $inmate, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $form = $this->createForm(InmateType::class, $inmate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', sprintf('Dossier detenu %s mis a jour.', $inmate->getUid()));

            return $this->redirectToRoute('app_inmate_show', ['id' => $inmate->getId()]);
        }

        return $this->render('inmate/form.html.twig', [
            'form' => $form,
            'inmate' => $inmate,
        ]);
    }
}
