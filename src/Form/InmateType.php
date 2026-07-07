<?php

namespace App\Form;

use App\Entity\Inmate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InmateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('uid', TextType::class, [
                'label' => 'Numero d\'ecrou (UID)',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prenom',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
            ])
            ->add('arrivalDate', DateType::class, [
                'label' => 'Date d\'arrivee',
                'widget' => 'single_text',
            ])
            ->add('releaseDate', DateType::class, [
                'label' => 'Date de sortie',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('securityLevel', ChoiceType::class, [
                'label' => 'Niveau de securite',
                'choices' => array_combine(Inmate::SECURITY_LEVELS, Inmate::SECURITY_LEVELS),
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => array_combine(Inmate::STATUSES, Inmate::STATUSES),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inmate::class,
        ]);
    }
}
