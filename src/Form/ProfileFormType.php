<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de modification du profil utilisateur.
 * Permet de changer son nom, son adresse, etc.
 */
class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('roleDisplay', TextType::class, [
                'label' => 'Rôle',
                'mapped' => false,
                'disabled' => true,
                'data' => in_array('ROLE_ADMIN', $options['data']->getRoles()) ? 'Administrateur' : (in_array('ROLE_TEACHER', $options['data']->getRoles()) ? 'Professeur' : 'Élève'),
                'attr' => ['class' => 'form-input']
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prenom',
                'attr' => [
                    'autocomplete' => 'given-name',
                    'class' => 'form-input',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'autocomplete' => 'family-name',
                    'class' => 'form-input',
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'autocomplete' => 'street-address',
                    'class' => 'form-input',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'autocomplete' => 'address-level2',
                    'class' => 'form-input',
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'autocomplete' => 'postal-code',
                    'class' => 'form-input',
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Mettre à jour le profil',
                'attr' => ['class' => 'btn btn--primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
