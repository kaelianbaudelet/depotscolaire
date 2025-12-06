<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Formulaire d'édition d'utilisateur pour les administrateurs.
 * C'est ici qu'on peut tout changer : rôles, suspension, infos persos...
 * Un grand pouvoir implique de grandes responsabilités.
 */
class AdminUserEditorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['class' => 'form-input'],
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'form-group'],
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                    'Modérateur' => 'ROLE_MODERATOR',
                ],
                'expanded' => true,
                'multiple' => true,
                'label' => 'Rôles',
                'label_attr' => ['class' => 'form-label'],
                'choice_attr' => function ($choice, $key, $value) {
                    return ['class' => 'form-checkbox-input'];
                },
                'attr' => ['class' => 'form-group'],
            ])
            ->add('isVerified', CheckboxType::class, [
                'label' => 'Email vérifié',
                'required' => false,
                'attr' => ['class' => 'form-checkbox-input'],
                'label_attr' => ['class' => 'form-checkbox-label'],
                'row_attr' => ['class' => 'form-checkbox-group'],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-input'],
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'form-group'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-input'],
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'form-group'],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse postale',
                'attr' => ['class' => 'form-input'],
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'form-group'],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['class' => 'form-input'],
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'form-group'],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'form-input'],
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'form-group'],
            ])
            ->add('createdAt', DateType::class, [
                'label' => 'Date de création',
                'disabled' => true,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-input'],
                'label_attr' => ['class' => 'form-label'],
                'row_attr' => ['class' => 'form-group'],
            ])
            ->add('isSuspended', CheckboxType::class, [
                'label' => 'Utilisateur suspendu ?',
                'required' => false,
                'attr' => ['class' => 'form-checkbox-input'],
                'label_attr' => ['class' => 'form-checkbox-label'],
                'row_attr' => ['class' => 'form-checkbox-group'],
            ])
            ->add('modifier', SubmitType::class, [
                'label' => 'Modifier',
                'attr' => ['class' => 'btn btn--primary'],
                'row_attr' => ['style' => 'text-align: center; margin-top: 2rem;'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
