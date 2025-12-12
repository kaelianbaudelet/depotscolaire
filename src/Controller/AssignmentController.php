<?php

namespace App\Controller;

use App\Entity\Assignment;
use App\Entity\Classroom;
use App\Entity\Submission;
use App\Form\AssignmentType;
use App\Form\SubmissionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/assignment')]
#[IsGranted('ROLE_USER')]
/**
 * Ce contrôleur est le chef d'orchestre pour tout ce qui touche aux devoirs.
 * C'est ici que ça se passe pour créer un devoir, le modifier, le supprimer, ou juste le regarder.
 * Seules les personnes connectées (ROLE_USER) ont le droit d'être ici.
 */
class AssignmentController extends AbstractController
{
    /**
     * Cette méthode permet au professeur de créer un nouveau devoir pour une classe spécifique.
     * On vérifie d'abord que c'est bien le prof de la classe qui essaie de créer le devoir (pas de triche !).
     * Ensuite, on gère le formulaire et l'upload des fichiers joints (les consignes).
     */
    #[Route('/new/{id}', name: 'app_assignment_create', methods: ['GET', 'POST'])]
    public function create(Request $request, Classroom $classroom, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // On s'assure que l'utilisateur actuel est bien le professeur de cette classe
        if ($classroom->getTeacher() !== $this->getUser()) {
             throw $this->createAccessDeniedException('Seul le professeur peut créer des devoirs.');
        }

        $assignment = new Assignment();
        $assignment->setClassroom($classroom);
        
        $form = $this->createForm(AssignmentType::class, $assignment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère les fichiers uploadés via le formulaire
            $attachmentFiles = $form->get('attachmentFiles')->getData();
            $attachments = [];

            if ($attachmentFiles) {
                foreach ($attachmentFiles as $attachmentFile) {
                    if ($attachmentFile) {
                        // On nettoie le nom du fichier pour éviter les problèmes
                        $originalFilename = pathinfo($attachmentFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$attachmentFile->guessExtension();

                        try {
                            // On déplace le fichier dans le dossier de stockage (configuré dans services.yaml)
                            $attachmentFile->move(
                                $this->getParameter('attachments_directory'),
                                $newFilename
                            );
                            $attachments[] = $newFilename;
                        } catch (FileException $e) {
                            $this->addFlash('danger', 'Oups, il y a eu un souci lors de l\'envoi du fichier de consigne.');
                        }
                    }
                }
                $assignment->setAttachments($attachments);
            }

            $entityManager->persist($assignment);
            $entityManager->flush();

            $this->addFlash('success', 'Le devoir a été créé avec succès. Au travail les élèves !');

            return $this->redirectToRoute('app_classroom_show', ['id' => $classroom->getId()]);
        }

        return $this->render('assignment/new.html.twig', [
            'classroom' => $classroom,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ici, on permet au professeur de modifier un devoir existant.
     * Utile si on a oublié une consigne ou si on veut changer la date limite.
     * Bien sûr, seul le prof qui a créé le devoir (ou le prof de la classe) peut le modifier.
     */
    #[Route('/{id}/edit', name: 'app_assignment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Assignment $assignment, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
         if ($assignment->getClassroom()->getTeacher() !== $this->getUser()) {
             throw $this->createAccessDeniedException('Seul le professeur peut modifier ce devoir.');
        }

        $form = $this->createForm(AssignmentType::class, $assignment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $attachmentFiles = $form->get('attachmentFiles')->getData();

            if ($attachmentFiles) {
                // On garde les fichiers déjà existants, on ne veut pas les perdre
                $currentAttachments = $assignment->getAttachments();
                $newAttachments = [];
                
                foreach ($attachmentFiles as $attachmentFile) {
                    if ($attachmentFile) {
                        $originalFilename = pathinfo($attachmentFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$attachmentFile->guessExtension();

                        try {
                            $attachmentFile->move(
                                $this->getParameter('attachments_directory'),
                                $newFilename
                            );
                            $newAttachments[] = $newFilename;
                        } catch (FileException $e) {
                            $this->addFlash('danger', 'Erreur lors de l\'upload d\'un fichier de consigne.');
                        }
                    }
                }
                
                // On fusionne les anciens et les nouveaux fichiers
                $assignment->setAttachments(array_merge($currentAttachments, $newAttachments));
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le devoir a été mis à jour.');

            return $this->redirectToRoute('app_assignment_show', ['id' => $assignment->getId()]);
        }

        return $this->render('assignment/edit.html.twig', [
            'assignment' => $assignment,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche les détails d'un devoir.
     * C'est la page principale pour un devoir.
     * - Le prof voit les détails et qui a rendu.
     * - L'élève voit les détails et un formulaire pour rendre son travail.
     */
    #[Route('/{id}', name: 'app_assignment_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Assignment $assignment, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        $classroom = $assignment->getClassroom();
        
        // Petite vérification de sécurité : est-ce que l'utilisateur a le droit de voir ce devoir ?
        if ($classroom->getTeacher() !== $user && !$classroom->getStudents()->contains($user)) {
             throw $this->createAccessDeniedException('Désolé, mais vous n\'avez pas accès à ce devoir.');
        }

        // Partie gestion du rendu pour les élèves
        $submission = null;
        $submissionForm = null;

        if ($classroom->getStudents()->contains($user)) {
            // Est-ce que l'élève a déjà rendu quelque chose ?
            $existingSubmission = $entityManager->getRepository(Submission::class)->findOneBy([
                'assignment' => $assignment,
                'student' => $user
            ]);
            
            if ($existingSubmission) {
                $submission = $existingSubmission;
            } else {
                 // Sinon, on prépare le formulaire pour rendre le devoir
                 $newSubmission = new Submission();
                 $submissionForm = $this->createForm(SubmissionType::class, $newSubmission);
                 $submissionForm->handleRequest($request);

                 if ($submissionForm->isSubmitted() && $submissionForm->isValid()) {
                     $files = $submissionForm->get('files')->getData();
                     $submittedFiles = [];

                     if ($files) {
                        foreach($files as $file) {
                             if ($file) {
                                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                $safeFilename = $slugger->slug($originalFilename);
                                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                                try {
                                    $file->move(
                                        $this->getParameter('submissions_directory'),
                                        $newFilename
                                    );
                                    $submittedFiles[] = $newFilename;
                                } catch (FileException $e) {
                                    $this->addFlash('danger', 'Problème lors de l\'envoi de votre fichier.');
                                    return $this->redirectToRoute('app_assignment_show', ['id' => $assignment->getId()]);
                                }
                             }
                        }

                        $newSubmission->setFiles($submittedFiles); 
                        $newSubmission->setStudent($user);
                        $newSubmission->setAssignment($assignment);
                        $newSubmission->setSubmittedAt(new \DateTimeImmutable());

                        $entityManager->persist($newSubmission);
                        $entityManager->flush();

                        $this->addFlash('success', 'Bravo ! Votre devoir a été rendu avec succès.');
                        return $this->redirectToRoute('app_assignment_show', ['id' => $assignment->getId()]);
                     }
                 }
            }
        }

        $isAssignmentOver = false;
        if ($assignment->getDueDate() && new \DateTime() > $assignment->getDueDate()) {
            $isAssignmentOver = true;
        }

        $editForm = null;
        if ($classroom->getTeacher() === $user) {
            $editForm = $this->createForm(AssignmentType::class, $assignment, [
                'action' => $this->generateUrl('app_assignment_edit', ['id' => $assignment->getId()]),
            ]);
        }

        return $this->render('assignment/show.html.twig', [
            'assignment' => $assignment,
            'submission' => $submission,
            'submission_form' => $submissionForm ? $submissionForm->createView() : null,
            'editForm' => $editForm ? $editForm->createView() : null,
            'is_assignment_over' => $isAssignmentOver,
        ]);
    }

    /**
     * Supprime un devoir.
     * Action irréversible, on supprime aussi tous les fichiers associés et les rendus des élèves.
     * C'est le grand nettoyage !
     */
    #[Route('/{id}/delete', name: 'app_assignment_delete', methods: ['POST'])]
    public function delete(Request $request, Assignment $assignment, EntityManagerInterface $entityManager): Response
    {
        if ($assignment->getClassroom()->getTeacher() !== $this->getUser()) {
             throw $this->createAccessDeniedException('Hop hop hop ! Seul le prof peut supprimer ça.');
        }

        if ($this->isCsrfTokenValid('delete'.$assignment->getId(), $request->request->get('_token'))) {
            // On supprime les fichiers de consignes
            foreach ($assignment->getAttachments() as $attachment) {
                $attachmentPath = $this->getParameter('attachments_directory') . '/' . $attachment;
                if (file_exists($attachmentPath)) {
                    unlink($attachmentPath);
                }
            }

            // On supprime aussi tous les fichiers rendus par les élèves pour ce devoir
            foreach ($assignment->getSubmissions() as $submission) {
                foreach ($submission->getFiles() as $file) {
                    $submissionPath = $this->getParameter('submissions_directory') . '/' . $file;
                    if (file_exists($submissionPath)) {
                        unlink($submissionPath);
                    }
                }
            }

            $entityManager->remove($assignment);
            $entityManager->flush();
            $this->addFlash('success', 'Le devoir a été supprimé.');
        }

        return $this->redirectToRoute('app_classroom_show', ['id' => $assignment->getClassroom()->getId()], Response::HTTP_SEE_OTHER);
    }

    /**
     * Permet de supprimer un fichier joint spécifique d'un devoir (une consigne).
     * Appelée en AJAX ou via un formulaire simple.
     */
    #[Route('/{id}/delete-attachment', name: 'app_assignment_delete_attachment', methods: ['POST'])]
    public function deleteAttachment(Request $request, Assignment $assignment, EntityManagerInterface $entityManager): Response
    {
        if ($assignment->getClassroom()->getTeacher() !== $this->getUser()) {
             throw $this->createAccessDeniedException('Accès refusé.');
        }

        $filename = $request->request->get('filename');
        if ($this->isCsrfTokenValid('delete_attachment'.$assignment->getId() . $filename, $request->request->get('_token'))) {
             $attachments = $assignment->getAttachments();
             $key = array_search($filename, $attachments);

             if ($key !== false) {
                 unset($attachments[$key]);
                 $assignment->setAttachments(array_values($attachments)); // On réindexe le tableau pour qu'il soit propre

                 // On supprime le vrai fichier sur le disque
                 $attachmentPath = $this->getParameter('attachments_directory') . '/' . $filename;
                 if (file_exists($attachmentPath)) {
                     unlink($attachmentPath);
                 }

                 $entityManager->flush();
                 $this->addFlash('success', 'Fichier supprimé.');
             } else {
                 $this->addFlash('danger', 'Impossible de trouver ce fichier.');
             }
        } else {
             $this->addFlash('danger', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('app_assignment_edit', ['id' => $assignment->getId()], Response::HTTP_SEE_OTHER);
    }

    /**
     * Permet à un élève d'annuler sa remise de devoir.
     * Cela supprime les fichiers et l'entrée en base.
     */
    #[Route('/{id}/cancel-submission', name: 'app_assignment_cancel_submission', methods: ['POST'])]
    public function cancelSubmission(Request $request, Assignment $assignment, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // On vérifie que c'est bien un élève de la classe
        if (!$assignment->getClassroom()->getStudents()->contains($user)) {
             throw $this->createAccessDeniedException('Vous ne faites pas partie de cette classe.');
        }

        $submission = $entityManager->getRepository(Submission::class)->findOneBy([
            'assignment' => $assignment,
            'student' => $user
        ]);

        if ($submission) {
             // Vérification de la date limite
            if ($assignment->getDueDate() && new \DateTime() > $assignment->getDueDate()) {
                 $this->addFlash('danger', 'La date limite est passée, vous ne pouvez plus annuler votre rendu.');
                 return $this->redirectToRoute('app_assignment_show', ['id' => $assignment->getId()]);
            }

            if ($this->isCsrfTokenValid('cancel_submission'.$submission->getId(), $request->request->get('_token'))) {
                // Suppression des fichiers physiques
                foreach ($submission->getFiles() as $file) {
                    $filePath = $this->getParameter('submissions_directory') . '/' . $file;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                $entityManager->remove($submission);
                $entityManager->flush();

                $this->addFlash('success', 'Votre devoir a été annulé. Vous pouvez déposer un nouveau fichier.');
            } else {
                $this->addFlash('danger', 'Token de sécurité invalide.');
            }
        }

        return $this->redirectToRoute('app_assignment_show', ['id' => $assignment->getId()]);
    }
}
