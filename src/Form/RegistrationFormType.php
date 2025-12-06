<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Le gros formulaire d'inscription.
 * On demande tout : nom, prénom, adresse... et le mot de passe (bien sécurisé bien sûr).
 */
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prenom',
                'attr' => [
                    'autocomplete' => 'given-name',
                    'placeholder' => 'Marie',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'autocomplete' => 'family-name',
                    'placeholder' => 'Curie',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'autocomplete' => 'email',
                    'placeholder' => 'exemple@domaine.fr',
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'autocomplete' => 'street-address',
                    'placeholder' => '1 rue de la Republique',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'autocomplete' => 'address-level2',
                    'placeholder' => 'Paris',
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'autocomplete' => 'postal-code',
                    'placeholder' => '75001',
                ],
            ])
            ->add('userRole', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Je suis...',
                'choices' => [
                    'Un étudiant' => 'student',
                    'Un professeur' => 'teacher',
                ],
                'expanded' => true, // Radio buttons
                'multiple' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez choisir votre statut.',
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'J\'accepte les conditions d\'utilisation',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d\'utilisation.',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // Au lieu d'être lié directement à l'entité,
                // ce champ est lu et encodé dans le contrôleur.
                'mapped' => false,
                'label' => 'Mot de passe',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un mot de passe.',
                    ]),
                    new Length([
                        'min' => 12,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caracteres.',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[\\W_]).+$/',
                        'message' => 'Votre mot de passe doit contenir au moins une minuscule, une majuscule, un chiffre et un caractere special.',
                    ]),
                    new NotCompromisedPassword([
                        'message' => 'Ce mot de passe a ete compromis dans une fuite de donnees. Merci d\'en choisir un autre.',
                    ]),
                ],
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
