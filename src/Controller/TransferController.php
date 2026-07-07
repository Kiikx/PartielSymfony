<?php

namespace App\Controller;

use App\Entity\Transfer;
use App\Entity\User;
use App\Exception\TransferException;
use App\Form\TransferRequestType;
use App\Repository\InmateRepository;
use App\Service\TransferService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TransferController extends AbstractController
{
    #[Route('/transfers/new', name: 'app_transfer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, TransferService $transferService, InmateRepository $inmateRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $preselectedInmate = $request->query->get('inmate');
        $form = $this->createForm(TransferRequestType::class, [
            'inmate' => $preselectedInmate ? $inmateRepository->find($preselectedInmate) : null,
            'type' => Transfer::TYPE_INTERNAL,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $actor = $this->getUser();
            \assert($actor instanceof User);

            $type = $form->get('type')->getData();
            $inmate = $form->get('inmate')->getData();
            $reason = $form->get('reason')->getData();

            try {
                if ($type === Transfer::TYPE_INTERNAL) {
                    $targetCell = $form->get('targetCell')->getData();
                    if ($targetCell === null) {
                        throw TransferException::targetCellRequired();
                    }

                    $transfer = $transferService->transferInternally($inmate, $targetCell, $actor, $reason);
                } else {
                    $transfer = $transferService->transferExternally($inmate, (string) $form->get('externalDestination')->getData(), $actor, $reason);
                }

                $this->addFlash('success', sprintf('Transfert %s enregistre pour %s.', strtolower($transfer->getType()), $inmate->getFullName()));

                return $this->redirectToRoute('app_inmate_show', ['id' => $inmate->getId()]);
            } catch (TransferException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('transfer/form.html.twig', [
            'form' => $form,
        ]);
    }
}
