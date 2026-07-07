<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class IncidentStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = array_combine($options['statuses'], $options['statuses']);

        $builder->add('status', ChoiceType::class, [
            'label' => 'Nouveau statut',
            'choices' => $choices,
            'constraints' => [new NotBlank(message: 'Selectionnez un statut.')],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'statuses' => [],
        ]);
        $resolver->setAllowedTypes('statuses', 'array');
    }
}
