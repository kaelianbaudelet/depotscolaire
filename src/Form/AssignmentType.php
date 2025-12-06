<?php

namespace App\Form;

use App\Entity\Assignment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\All;

/**
 * Formulaire de création/édition d'un devoir.
 * Permet au prof de définir le titre, la consigne, la date limite et d'ajouter des fichiers.
 */
class AssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du devoir',
                'attr' => ['class' => 'form-input']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-input', 'rows' => 5],
                'required' => false,
            ])
            ->add('dueDate', DateTimeType::class, [
                'label' => 'Date limite de rendu',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-input'],
                'required' => false,
            ])
            ->add('allowLateSubmissions', CheckboxType::class, [
                'label' => 'Autoriser les rendus en retard ?',
                'label_attr' => ['class' => 'form-checkbox-label'],
                'attr' => ['class' => 'form-checkbox-input'],
                'row_attr' => ['class' => 'form-checkbox-group'],
                'required' => false,
            ])
            // Champ non mappé à l'entité directement, géré manuellement dans le contrôleur
            ->add('attachmentFiles', FileType::class, [
                'label' => 'Fichiers de consigne (PDF, Word, Image)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '10M',
                                'mimeTypes' => [
                                    'application/pdf',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'image/jpeg',
                                    'image/png',
                                ],
                                'mimeTypesMessage' => 'Veuillez uploader un document valide (PDF, Word, Image)',
                            ])
                        ]
                    ])
                ],
                'attr' => ['class' => 'form-input', 'multiple' => 'multiple']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Assignment::class,
        ]);
    }
}
