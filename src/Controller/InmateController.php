<?php

namespace App\Controller;

use App\Entity\Inmate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InmateController extends AbstractController
{
    #[Route('/inmates/new', name: 'app_inmate_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('inmate/new.html.twig');
    }

    #[Route('/inmates/{id<\d+>}/edit', name: 'app_inmate_edit', methods: ['GET'])]
    public function edit(Inmate $inmate): Response
    {
        return $this->render('inmate/edit.html.twig', [
            'inmate' => $inmate,
        ]);
    }

    #[Route('/inmates/{id<\d+>}', name: 'app_inmate_show', methods: ['GET'])]
    public function show(Inmate $inmate): Response
    {
        return $this->render('inmate/show.html.twig', [
            'inmate' => $inmate,
        ]);
    }

    #[Route('/inmates', name: 'app_inmate_index')]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $databaseReady = true;
        $filters = [
            'uid' => trim((string) $request->query->get('uid', '')),
            'status' => trim((string) $request->query->get('status', '')),
            'securityLevel' => trim((string) $request->query->get('securityLevel', '')),
        ];

        try {
            $repository = $entityManager->getRepository(Inmate::class);
            $qb = $repository->createQueryBuilder('inmate')->orderBy('inmate.arrivalDate', 'DESC')->setMaxResults(30);

            if ($filters['uid'] !== '') {
                $qb->andWhere('inmate.uid LIKE :uid')->setParameter('uid', '%'.mb_strtoupper($filters['uid']).'%');
            }

            if ($filters['status'] !== '') {
                $qb->andWhere('inmate.status = :status')->setParameter('status', $filters['status']);
            }

            if ($filters['securityLevel'] !== '') {
                $qb->andWhere('inmate.securityLevel = :securityLevel')->setParameter('securityLevel', $filters['securityLevel']);
            }

            $inmates = $qb->getQuery()->getResult();
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
            'filters' => $filters,
            'statuses' => Inmate::STATUSES,
            'securityLevels' => Inmate::SECURITY_LEVELS,
        ]);
    }
}
