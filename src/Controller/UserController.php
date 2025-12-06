<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use App\Form\AdminUserEditorType;
use App\Entity\User;
use App\Entity\Logs;
use App\Repository\LogsRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Contrôleur de gestion des utilisateurs (partie Admin).
 * Ici, on a les pleins pouvoirs : voir tout le monde, modifier les profils...
 * À utiliser avec sagesse !
 */
final class UserController extends AbstractController
{
    /**
     * Affiche la liste complète des utilisateurs et des logs.
     * Une vue d'ensemble pour savoir ce qui se passe sur le site.
     */
    #[Route('/adm-administration', name: 'app_users_list')] 
    public function users_list(UserRepository $userRepository, LogsRepository $logsRepository, FormFactoryInterface $formFactory): Response
    {
        $users = $userRepository->findAll();
        $logs = $logsRepository->findAll();
        $forms = [];

        foreach ($users as $user) {
            $form = $formFactory->createNamed(
                'user_edit_' . $user->getId(),
                AdminUserEditorType::class,
                $user,
                [
                    'action' => $this->generateUrl('app_admin_user_update', ['id' => $user->getId()]),
                    'method' => 'POST',
                ]
            );
            $forms[$user->getId()] = $form->createView();
        }

        return $this->render('admin/users_list.html.twig', [
            'users' => $users,
            'logs' => $logs,
            'forms' => $forms,
        ]);
    }

    #[Route('/admin/user/{id}/update', name: 'app_admin_user_update', methods: ['POST'])]
    public function updateUser(Request $request, User $user, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->createNamed(
            'user_edit_' . $user->getId(),
            AdminUserEditorType::class,
            $user
        );
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', "Utilisateur {$user->getEmail()} modifié avec succès.");
        } else {
             // On pourrait rediriger avec les erreurs, mais pour l'instant simple redirect
             $this->addFlash('danger', "Erreur lors de la modification de {$user->getEmail()}. Vérifiez les données.");
        }

        return $this->redirectToRoute('app_users_list');
    }
    /**
     * Éditeur de liste d'utilisateurs.
     * Permet de modifier plusieurs utilisateurs à la volée.
     * On génère un formulaire pour chaque utilisateur dans la liste. C'est puissant !
     */
    #[Route('/adm-users-list_editor', name: 'app_users_list_editor')]
    public function usersListEditor(Request $request, EntityManagerInterface $em, FormFactoryInterface $formFactory): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        $forms = [];

        foreach ($users as $user) {
            // On crée un formulaire nommé dynamiquement pour chaque user (user_1, user_2...)
            $form = $formFactory->createNamed(
                'user_'.$user->getId(),       
                AdminUserEditorType::class, 
                $user                        
            );
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em->flush();
                $this->addFlash('notice', "Utilisateur {$user->getEmail()} modifié avec succès");
                return $this->redirectToRoute('app_users_list_editor');
            }
            $forms[$user->getId()] = $form->createView();
        }

        return $this->render('admin/users_list_editor.html.twig', ['users' => $users,'forms' => $forms,]);
    }
    
    /**
     * Affiche une vue simple des profils admin.
     * (Semble être une autre vue de liste, peut-être pour une autre fonctionnalité ?)
     */
    #[Route('/adm-profil_admin', name: 'app_profil_admin')] 
    public function profil_admin(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/profil_admin.html.twig', ['users' => $users]);
    }

    /**
     * Page d'édition des administrateurs.
     * En théorie, c'est ici qu'on gère les droits spécifiques des admins.
     */
    #[Route('/adm-edit_user_admin', name: 'app_edit_user_admin')] 
    public function edit_user_admin(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/edit_user_admin.html.twig', ['users' => $users]);
    }

    /**
     * Supprime un utilisateur définitivement.
     * Pas de retour en arrière possible (sauf backup BDD).
     */
    #[Route('/admin/user/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_users_list');
    }

    /**
     * Active ou désactive (suspend) un utilisateur.
     */
    #[Route('/admin/user/{id}/toggle-ban', name: 'app_admin_user_toggle_ban', methods: ['POST'])]
    public function toggleBan(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle_ban'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsSuspended(!$user->isSuspended());
            $entityManager->flush();
            
            $status = $user->isSuspended() ? 'suspendu' : 'réactivé';
            $this->addFlash('success', "Utilisateur $status avec succès.");
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_users_list');
    }
    
}
