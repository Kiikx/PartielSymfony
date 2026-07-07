<?php

namespace App\Form;

use App\Entity\Activity;
use App\Entity\ActivityParticipation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Not entity-mapped: ActivityParticipationService handles create-or-update.
 */
class ParticipationCheckInType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('activity', EntityType::class, [
                'label' => 'Activite',
                'class' => Activity::class,
                'choice_label' => 'label',
                'choices' => $options['activities'],
                'constraints' => [new NotBlank(message: 'Selectionnez une activite.')],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => array_combine(ActivityParticipation::STATUSES, ActivityParticipation::STATUSES),
            ])
            ->add('inmateId', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'activities' => [],
        ]);
        $resolver->setAllowedTypes('activities', 'array');
    }
}
