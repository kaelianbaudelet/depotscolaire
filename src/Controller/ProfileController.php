<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\InvalidCurrentPasswordException;
use App\Exception\PasswordReuseException;
use App\Form\ChangePasswordFormType;
use App\Form\ProfileFormType;
use App\Service\UserPasswordManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager, UserPasswordManager $passwordManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $profileForm = $this->createForm(ProfileFormType::class, $user);
        $profileForm->handleRequest($request);

        $passwordForm = $this->createForm(ChangePasswordFormType::class);
        $passwordForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a ete mis a jour.');

            return $this->redirectToRoute('app_profile');
        }

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $currentPassword = (string) $passwordForm->get('currentPassword')->getData();
            $newPassword = (string) $passwordForm->get('newPassword')->getData();

            if ($currentPassword === $newPassword) {
                $passwordForm->get('newPassword')->addError(new FormError('Votre nouveau mot de passe doit etre different du mot de passe actuel.'));
            } else {
                try {
                    $passwordManager->changePassword($user, $currentPassword, $newPassword);
                    $this->addFlash('success', 'Votre mot de passe a ete mis a jour.');

                    return $this->redirectToRoute('app_profile');
                } catch (InvalidCurrentPasswordException) {
                    $passwordForm->get('currentPassword')->addError(new FormError('Votre mot de passe actuel est incorrect.'));
                } catch (PasswordReuseException) {
                    $passwordForm->get('newPassword')->addError(new FormError('Vous ne pouvez pas reutiliser un ancien mot de passe.'));
                }
            }
        }

        return $this->render('profile/index.html.twig', [
            'profileForm' => $profileForm->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }
}
