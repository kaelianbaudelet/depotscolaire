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

class AdminUserEditorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['class' => 'form-control mb-2'],
                'label_attr' => ['class' => 'form-label'],
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
                    return ['class' => 'form-check-input'];
                },
                'attr' => ['class' => 'mb-2'],
            ])
            ->add('isVerified', CheckboxType::class, [
                'label' => 'Email vérifié',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'row_attr' => ['class' => 'form-check mb-2'],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control mb-2'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control mb-2'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse postale',
                'attr' => ['class' => 'form-control mb-2'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['class' => 'form-control mb-2'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'form-control mb-2'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('createdAt', DateType::class, [
                'label' => 'Date de création',
                'disabled' => true,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control mb-2'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('isSuspended', CheckboxType::class, [
                'label' => 'Utilisateur suspendu ?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'row_attr' => ['class' => 'form-check mb-4'],
            ])
            ->add('modifier', SubmitType::class, [
                'label' => 'Modifier',
                'attr' => ['class' => 'btn btn-primary text-white m-4'],
                'row_attr' => ['class' => 'text-center'],
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
