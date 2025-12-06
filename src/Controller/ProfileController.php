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

/**
 * Ce contrôleur gère la page de profil de l'utilisateur.
 * C'est ici qu'on peut changer son mot de passe ou mettre à jour ses infos personnelles.
 * Un véritable espace personnel !
 */
final class ProfileController extends AbstractController
{
    /**
     * Affiche et gère le formulaire de profil.
     * On y change son nom, son prénom, et son mot de passe si on le souhaite.
     * On fait attention à bien vérifier l'ancien mot de passe avant de le changer.
     */
    #[Route('/profil', name: 'app_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager, UserPasswordManager $passwordManager): Response
    {
        // On vérifie que l'utilisateur est bien connecté
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Formulaire général (Nom, Prénom, Email...)
        $profileForm = $this->createForm(ProfileFormType::class, $user);
        $profileForm->handleRequest($request);

        // Formulaire spécifique pour le changement de mot de passe
        $passwordForm = $this->createForm(ChangePasswordFormType::class);
        $passwordForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_profile');
        }

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $currentPassword = (string) $passwordForm->get('currentPassword')->getData();
            $newPassword = (string) $passwordForm->get('newPassword')->getData();

            if ($currentPassword === $newPassword) {
                $passwordForm->get('newPassword')->addError(new FormError('Allez, un petit effort ! Le nouveau mot de passe doit être différent de l\'ancien.'));
            } else {
                try {
                    $passwordManager->changePassword($user, $currentPassword, $newPassword);
                    $this->addFlash('success', 'Votre mot de passe a été changé. Ne l\'oubliez pas !');

                    return $this->redirectToRoute('app_profile');
                } catch (InvalidCurrentPasswordException) {
                    $passwordForm->get('currentPassword')->addError(new FormError('Ce n\'est pas le bon mot de passe actuel.'));
                } catch (PasswordReuseException) {
                    $passwordForm->get('newPassword')->addError(new FormError('Vous avez déjà utilisé ce mot de passe récemment. Essayez-en un autre.'));
                }
            }
        }

        return $this->render('profile/index.html.twig', [
            'profileForm' => $profileForm->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }
}
