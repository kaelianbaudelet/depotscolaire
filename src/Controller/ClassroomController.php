<?php

namespace App\Controller;

use App\Entity\Classroom;
use App\Entity\Assignment;
use App\Form\ClassroomType;
use App\Form\AssignmentType;

use App\Repository\ClassroomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/classroom')]
#[IsGranted('ROLE_USER')]
/**
 * Ce contrôleur gère tout ce qui se rapporte aux classes.
 * Création de classe, invitation d'élèves, affichage du tableau de bord d'une classe...
 * C'est le cœur de la gestion des groupes d'élèves.
 */
class ClassroomController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(string:MAILER_FROM_EMAIL)%')] private readonly string $mailerFromEmail,
        #[Autowire('%env(string:MAILER_FROM_NAME)%')] private readonly string $mailerFromName,
        private readonly UriSigner $uriSigner,
    ) {
    }

    /**
     * Invite un élève par email.
     * Envoie une invitation formelle pour rejoindre la classe.
     */
    #[Route('/{id}/invite-email', name: 'app_classroom_invite_email', methods: ['POST'])]
    public function inviteEmail(
        Request $request, 
        Classroom $classroom, 
        \App\Repository\UserRepository $userRepository,
        MailerInterface $mailer
    ): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $classroom->getTeacher() !== $this->getUser()) {
             return $this->json(['error' => 'Non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user) {
            // Génération du lien d'invitation signé avec expiration (24h)
            $expiration = new \DateTime('+1 day');
            // On utilise un timestamp pour l'expiration
            $joinUrl = $this->generateUrl('app_classroom_join', [
                'id' => $classroom->getId(),
                'expires' => $expiration->getTimestamp(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            // On signe l'URL
            $signedUrl = $this->uriSigner->sign($joinUrl);

            $emailMessage = (new Email())
                ->from(new Address($this->mailerFromEmail, $this->mailerFromName))
                ->to($email)
                ->subject('Invitation à rejoindre la classe ' . $classroom->getName())
                ->html(
                    '<p>Bonjour,</p>' .
                    '<p>Votre professeur vous invite à rejoindre la classe <strong>' . $classroom->getName() . '</strong>.</p>' .
                    '<p><a href="' . $signedUrl . '">Cliquez ici pour rejoindre la classe</a></p>' .
                    '<p><small>Ce lien expire le ' . $expiration->format('d/m/Y à H:i') . '</small></p>'
                );

            $mailer->send($emailMessage);

            return $this->json(['success' => true, 'message' => 'Invitation envoyée à ' . $email]);
        }

        return $this->json(['error' => 'Aucun utilisateur trouvé avec cet email'], 404);
    }

    /**
     * Permet à un élève de rejoindre une classe via un lien signé.
     */
    #[Route('/{id}/join', name: 'app_classroom_join', methods: ['GET'])]
    public function join(Request $request, Classroom $classroom, EntityManagerInterface $entityManager): Response
    {
        // 1. Vérifier la signature de l'URL
        if (!$this->uriSigner->check($request->getUri())) {
            $this->addFlash('error', 'Lien d\'invitation invalide ou modifié.');
            return $this->redirectToRoute('app_classroom_index');
        }

        // 2. Vérifier l'expiration
        $expires = $request->query->get('expires');
        if ($expires && time() > $expires) {
            $this->addFlash('error', 'Ce lien d\'invitation a expiré.');
            return $this->redirectToRoute('app_classroom_index');
        }

        $user = $this->getUser();

        // 3. Vérifier si l'utilisateur est déjà dans la classe ou est le prof
        if ($classroom->getTeacher() === $user || $classroom->getStudents()->contains($user)) {
            $this->addFlash('info', 'Vous êtes déjà membre de cette classe.');
            return $this->redirectToRoute('app_classroom_show', ['id' => $classroom->getId()]);
        }

        // 4. Ajouter l'élève
        $classroom->addStudent($user);
        $entityManager->flush();

        $this->addFlash('success', 'Bienvenue ! Vous avez rejoint la classe ' . $classroom->getName() . '.');

        return $this->redirectToRoute('app_classroom_show', ['id' => $classroom->getId()]);
    }

    /**
     * Page d'accueil des classes.
     * Affiche la liste des classes et permet d'en créer une nouvelle (via modale).
     */
    #[Route('/', name: 'app_classroom_index', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager, ClassroomRepository $classroomRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Formulaire de création (pour la modale)
        $newClassroom = new Classroom();
        $form = $this->createForm(ClassroomType::class, $newClassroom);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérification simple : prof uniquement
            if (!$this->isGranted('ROLE_TEACHER')) {
                 throw $this->createAccessDeniedException('Seuls les professeurs peuvent créer des classes.');
            }

            $newClassroom->setTeacher($user);
            $entityManager->persist($newClassroom);
            $entityManager->flush();

            $this->addFlash('success', 'Votre nouvelle classe a été créée !');

            return $this->redirectToRoute('app_classroom_index', [], Response::HTTP_SEE_OTHER);
        }
        
        if ($this->isGranted('ROLE_ADMIN')) {
            $classrooms = $classroomRepository->findAll();
             return $this->render('classroom/index.html.twig', [
                'teaching_classrooms' => $classrooms, 
                'joined_classrooms' => [],
                'form' => $form->createView(),
            ]);
        }

        return $this->render('classroom/index.html.twig', [
            'teaching_classrooms' => $user->getTeachingClassrooms(),
            'joined_classrooms' => $user->getJoinedClassrooms(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie les informations d'une classe (nom, description...).
     */
    #[Route('/{id}/edit', name: 'app_classroom_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Classroom $classroom, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $classroom->getTeacher() !== $this->getUser()) {
             throw $this->createAccessDeniedException('Vous n\'êtes pas le professeur de cette classe, bas les pattes !');
        }

        $form = $this->createForm(ClassroomType::class, $classroom);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Modifications enregistrées.');

            return $this->redirectToRoute('app_classroom_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('classroom/edit.html.twig', [
            'classroom' => $classroom,
            'form' => $form->createView(),
        ]);
    }



    /**
     * Affiche le tableau de bord d'une classe.
     * C'est là qu'on voit les devoirs, les élèves, et le QR Code pour inviter.
     */
    #[Route('/{id}', name: 'app_classroom_show', methods: ['GET'])]
    public function show(Classroom $classroom, Request $request): Response
    {
        // Vérification d'accès : il faut être soit le prof soit un élève de la classe, OU ADMIN
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $classroom->getTeacher() !== $user && !$classroom->getStudents()->contains($user)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas la clé de cette salle de classe !');
        }

        // Formulaire d'édition (pour la modale)
        $editForm = null;
        $assignmentForm = null;

        if ($classroom->getTeacher() === $user) {
            $editForm = $this->createForm(ClassroomType::class, $classroom, [
                'action' => $this->generateUrl('app_classroom_edit', ['id' => $classroom->getId()]),
            ]);

            // Formulaire pour créer un nouveau devoir
            $newAssignment = new Assignment();
            $newAssignment->setClassroom($classroom);
            $assignmentForm = $this->createForm(AssignmentType::class, $newAssignment, [
                'action' => $this->generateUrl('app_assignment_create', ['id' => $classroom->getId()]),
            ]);
        }

        return $this->render('classroom/show.html.twig', [
            'classroom' => $classroom,
            'editForm' => $editForm ? $editForm->createView() : null,
            'assignmentForm' => $assignmentForm ? $assignmentForm->createView() : null,
        ]);
    }

    /**
     * Permet au professeur de renvoyer un élève de la classe.
     * C'est triste, mais parfois nécessaire.
     */
    #[Route('/{id}/remove-student/{student_id}', name: 'app_classroom_remove_student', methods: ['POST'])]
    public function removeStudent(Request $request, Classroom $classroom, int $student_id, \App\Repository\UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $classroom->getTeacher() !== $this->getUser()) {
             throw $this->createAccessDeniedException('Action réservée au professeur.');
        }

        $student = $userRepository->find($student_id);

        if (!$student) {
            throw $this->createNotFoundException('Élève introuvable.');
        }

        if (!$classroom->getStudents()->contains($student)) {
             $this->addFlash('warning', 'Cet élève n\'est pas dans cette classe.');
        } else {
             if ($this->isCsrfTokenValid('remove_student'.$student->getId(), $request->request->get('_token'))) {
                 $classroom->removeStudent($student);
                 $entityManager->flush();
                 $this->addFlash('success', 'L\'élève ' . $student->getFirstName() . ' ' . $student->getLastName() . ' a été retiré.');
             } else {
                 $this->addFlash('danger', 'Token de sécurité invalide.');
             }
        }

        return $this->redirectToRoute('app_classroom_show', ['id' => $classroom->getId()], Response::HTTP_SEE_OTHER);
    }

    /**
     * Supprime définitivement une classe et tout son contenu.
     * Attention, c'est radical !
     */
    #[Route('/{id}/delete', name: 'app_classroom_delete', methods: ['POST'])]
    public function delete(Request $request, Classroom $classroom, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $classroom->getTeacher() !== $this->getUser()) {
             throw $this->createAccessDeniedException('Seul le professeur peut supprimer la classe.');
        }

        if ($this->isCsrfTokenValid('delete'.$classroom->getId(), $request->request->get('_token'))) {
            $entityManager->remove($classroom);
            $entityManager->flush();
            $this->addFlash('success', 'La classe a été supprimée.');
        }

        return $this->redirectToRoute('app_classroom_index', [], Response::HTTP_SEE_OTHER);
    }
}
