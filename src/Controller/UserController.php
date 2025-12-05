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

final class UserController extends AbstractController
{
    #[Route('/adm-users-list', name: 'app_users_list')] 
    public function users_list(UserRepository $userRepository, LogsRepository $logsRepository): Response
    {
        $users = $userRepository->findAll();
        $logs = $logsRepository->findAll();
        return $this->render('admin/users_list.html.twig', [
            'users' => $users,
            'logs' => $logs,
        ]);
    }

    #[Route('/adm-users-list_editor', name: 'app_users_list_editor')]
    public function usersListEditor(Request $request, EntityManagerInterface $em, FormFactoryInterface $formFactory): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        $forms = [];

        foreach ($users as $user) {
            $form = $formFactory->createNamed(
                'user_'.$user->getId(),       
                AdminUserEditorType::class, 
                $user                        
            );
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em->flush();
                $this->addFlash('notice', "Utilisateur {$user->getEmail()} modifié");
                return $this->redirectToRoute('app_users_list_editor');
            }
            $forms[$user->getId()] = $form->createView();
        }

        return $this->render('admin/users_list_editor.html.twig', ['users' => $users,'forms' => $forms,]);
    }
    

    #[Route('/adm-profil_admin', name: 'app_profil_admin')] 
    public function profil_admin(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/profil_admin.html.twig', ['users' => $users]);
    }

    #[Route('/adm-edit_user_admin', name: 'app_edit_user_admin')] 
    public function edit_user_admin(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/edit_user_admin.html.twig', ['users' => $users]);
    }
    
}
