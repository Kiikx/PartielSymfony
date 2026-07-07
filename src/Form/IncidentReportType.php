<?php

namespace App\Form;

use App\Entity\Cell;
use App\Entity\Incident;
use App\Entity\Inmate;
use App\Repository\CellRepository;
use App\Repository\InmateRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Not entity-mapped: IncidentService owns creation, notifications and audit logs.
 */
class IncidentReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [new NotBlank(message: 'Le titre est obligatoire.')],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [new NotBlank(message: 'La description est obligatoire.')],
            ])
            ->add('severity', ChoiceType::class, [
                'label' => 'Gravite',
                'choices' => array_combine(Incident::SEVERITIES, Incident::SEVERITIES),
            ])
            ->add('occurredAt', DateTimeType::class, [
                'label' => 'Date et heure',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('cell', EntityType::class, [
                'label' => 'Cellule concernee',
                'class' => Cell::class,
                'required' => false,
                'choice_label' => fn (Cell $cell): string => sprintf('%s (%s - %s)', $cell->getNumber(), $cell->getWing()->getBuilding()->getName(), $cell->getWing()->getName()),
                'group_by' => fn (Cell $cell): string => $cell->getWing()->getBuilding()->getName().' - '.$cell->getWing()->getName(),
                'query_builder' => fn (CellRepository $repository) => $repository->createQueryBuilder('cell')
                    ->innerJoin('cell.wing', 'wing')
                    ->innerJoin('wing.building', 'building')
                    ->orderBy('building.name', 'ASC')
                    ->addOrderBy('wing.name', 'ASC')
                    ->addOrderBy('cell.number', 'ASC'),
                'placeholder' => 'Hors cellule',
            ])
            ->add('inmates', EntityType::class, [
                'label' => 'Detenus impliques',
                'class' => Inmate::class,
                'required' => false,
                'multiple' => true,
                'choice_label' => fn (Inmate $inmate): string => sprintf('%s - %s', $inmate->getUid(), $inmate->getFullName()),
                'query_builder' => fn (InmateRepository $repository) => $repository->createQueryBuilder('inmate')
                    ->orderBy('inmate.uid', 'ASC'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
