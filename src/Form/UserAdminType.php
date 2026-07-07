<?php

namespace App\Form;

use App\Entity\Building;
use App\Entity\Wing;
use App\Repository\BuildingRepository;
use App\Repository\WingRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Not entity-mapped: User inheritance requires choosing the concrete class in the controller.
 */
class UserAdminType extends AbstractType
{
    public const PROFILE_ADMIN = 'admin';
    public const PROFILE_MANAGER = 'manager';
    public const PROFILE_GUARD = 'guard';

    public const PROFILES = [
        self::PROFILE_ADMIN,
        self::PROFILE_MANAGER,
        self::PROFILE_GUARD,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('profileType', ChoiceType::class, [
                'label' => 'Profil',
                'choices' => [
                    'Administrateur' => self::PROFILE_ADMIN,
                    'Manager' => self::PROFILE_MANAGER,
                    'Surveillant' => self::PROFILE_GUARD,
                ],
                'disabled' => $isEdit,
                'help' => $isEdit ? 'Le profil est verrouille apres creation pour conserver le type Doctrine.' : null,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(message: 'L email est obligatoire.'),
                    new Email(message: 'L email est invalide.'),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prenom',
                'constraints' => [new NotBlank(message: 'Le prenom est obligatoire.')],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new NotBlank(message: 'Le nom est obligatoire.')],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $isEdit ? 'Nouveau mot de passe' : 'Mot de passe',
                'required' => !$isEdit,
                'constraints' => array_filter([
                    !$isEdit ? new NotBlank(message: 'Le mot de passe est obligatoire.') : null,
                    new Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caracteres.'),
                ]),
                'help' => $isEdit ? 'Laissez vide pour conserver le mot de passe actuel.' : null,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Compte actif',
                'required' => false,
            ])
            ->add('service', TextType::class, [
                'label' => 'Service administrateur',
                'required' => false,
            ])
            ->add('superAdmin', CheckboxType::class, [
                'label' => 'Super administrateur',
                'required' => false,
            ])
            ->add('managedBuilding', EntityType::class, [
                'label' => 'Batiment gere',
                'class' => Building::class,
                'required' => false,
                'placeholder' => 'Aucun batiment',
                'choice_label' => 'name',
                'query_builder' => fn (BuildingRepository $repository) => $repository->createQueryBuilder('building')
                    ->orderBy('building.name', 'ASC'),
            ])
            ->add('badgeNumber', TextType::class, [
                'label' => 'Numero de badge',
                'required' => false,
            ])
            ->add('assignedZone', EntityType::class, [
                'label' => 'Zone assignee',
                'class' => Wing::class,
                'required' => false,
                'placeholder' => 'Aucune zone',
                'choice_label' => fn (Wing $wing): string => sprintf('%s - %s', $wing->getBuilding()?->getName() ?? 'Batiment', $wing->getName()),
                'group_by' => fn (Wing $wing): string => $wing->getBuilding()?->getName() ?? 'Batiment',
                'query_builder' => fn (WingRepository $repository) => $repository->createQueryBuilder('wing')
                    ->leftJoin('wing.building', 'building')
                    ->addOrderBy('building.name', 'ASC')
                    ->addOrderBy('wing.name', 'ASC'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'is_edit' => false,
        ]);
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
