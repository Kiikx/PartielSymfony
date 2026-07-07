<?php

namespace App\Form;

use App\Entity\Cell;
use App\Entity\Inmate;
use App\Repository\CellRepository;
use App\Repository\InmateRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Not entity-mapped: the actual Assignment is built by AssignmentService,
 * which also enforces the business rules (capacity, inmate status, ...).
 */
class AssignmentRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('inmate', EntityType::class, [
                'label' => 'Detenu',
                'class' => Inmate::class,
                'choice_label' => fn (Inmate $inmate): string => sprintf('%s - %s', $inmate->getUid(), $inmate->getFullName()),
                'query_builder' => fn (InmateRepository $repository) => $repository->createAssignableQueryBuilder(),
                'constraints' => [new NotBlank(message: 'Selectionnez un detenu.')],
            ])
            ->add('cell', EntityType::class, [
                'label' => 'Cellule',
                'class' => Cell::class,
                'choice_label' => fn (Cell $cell): string => sprintf('%s (%s - %s)', $cell->getNumber(), $cell->getWing()->getBuilding()->getName(), $cell->getWing()->getName()),
                'group_by' => fn (Cell $cell): string => $cell->getWing()->getBuilding()->getName().' - '.$cell->getWing()->getName(),
                'query_builder' => fn (CellRepository $repository) => $repository->createAvailableForAssignmentQueryBuilder(),
                'constraints' => [new NotBlank(message: 'Selectionnez une cellule disponible.')],
            ])
            ->add('reason', TextareaType::class, [
                'label' => 'Motif',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
