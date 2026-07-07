<?php

namespace App\Form;

use App\Entity\Cell;
use App\Entity\Inmate;
use App\Entity\Transfer;
use App\Repository\CellRepository;
use App\Repository\InmateRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Not entity-mapped: the actual Transfer is built by TransferService,
 * which also enforces the business rules and required-field logic per type.
 */
class TransferRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('inmate', EntityType::class, [
                'label' => 'Detenu',
                'class' => Inmate::class,
                'choice_label' => fn (Inmate $inmate): string => sprintf('%s - %s', $inmate->getUid(), $inmate->getFullName()),
                'query_builder' => fn (InmateRepository $repository) => $repository->createTransferableQueryBuilder(),
                'constraints' => [new NotBlank(message: 'Selectionnez un detenu.')],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de transfert',
                'choices' => [
                    'Interne' => Transfer::TYPE_INTERNAL,
                    'Externe' => Transfer::TYPE_EXTERNAL,
                ],
                'expanded' => true,
                'attr' => ['class' => 'transfer-type-toggle'],
                'row_attr' => ['class' => 'form-field radio-inline'],
            ])
            ->add('targetCell', EntityType::class, [
                'label' => 'Cellule cible',
                'class' => Cell::class,
                'required' => false,
                'choice_label' => fn (Cell $cell): string => sprintf('%s (%s - %s)', $cell->getNumber(), $cell->getWing()->getBuilding()->getName(), $cell->getWing()->getName()),
                'group_by' => fn (Cell $cell): string => $cell->getWing()->getBuilding()->getName().' - '.$cell->getWing()->getName(),
                'query_builder' => fn (CellRepository $repository) => $repository->createAvailableForAssignmentQueryBuilder(),
                'row_attr' => ['class' => 'form-field transfer-field-internal'],
            ])
            ->add('externalDestination', TextType::class, [
                'label' => 'Destination externe',
                'required' => false,
                'row_attr' => ['class' => 'form-field transfer-field-external'],
            ])
            ->add('reason', TextareaType::class, [
                'label' => 'Motif',
                'constraints' => [new NotBlank(message: 'Le motif est obligatoire.')],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
