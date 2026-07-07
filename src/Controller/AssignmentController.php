<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\AssignmentException;
use App\Form\AssignmentRequestType;
use App\Repository\InmateRepository;
use App\Service\AssignmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AssignmentController extends AbstractController
{
    #[Route('/assignments/new', name: 'app_assignment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, AssignmentService $assignmentService, InmateRepository $inmateRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $preselectedInmate = $request->query->get('inmate');
        $form = $this->createForm(AssignmentRequestType::class, [
            'inmate' => $preselectedInmate ? $inmateRepository->find($preselectedInmate) : null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $actor = $this->getUser();
            \assert($actor instanceof User);

            try {
                $assignment = $assignmentService->assign(
                    $form->get('inmate')->getData(),
                    $form->get('cell')->getData(),
                    $actor,
                    $form->get('reason')->getData(),
                );

                $this->addFlash('success', sprintf('Detenu affecte a la cellule %s.', $assignment->getCell()->getNumber()));

                return $this->redirectToRoute('app_inmate_show', ['id' => $assignment->getInmate()->getId()]);
            } catch (AssignmentException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('assignment/form.html.twig', [
            'form' => $form,
        ]);
    }
}
