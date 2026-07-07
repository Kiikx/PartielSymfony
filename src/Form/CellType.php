<?php

namespace App\Form;

use App\Entity\Cell;
use App\Entity\Wing;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CellType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('number', TextType::class, [
                'label' => 'Numero',
            ])
            ->add('capacity', IntegerType::class, [
                'label' => 'Capacite',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => array_combine(Cell::STATUSES, Cell::STATUSES),
            ])
            ->add('wing', EntityType::class, [
                'label' => 'Aile',
                'class' => Wing::class,
                'choice_label' => 'name',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cell::class,
        ]);
    }
}
