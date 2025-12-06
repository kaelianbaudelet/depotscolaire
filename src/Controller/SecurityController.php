<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Contrôleur de sécurité.
 * C'est le videur de la boîte de nuit. Il gère qui rentre (login) et qui sort (logout).
 */
class SecurityController extends AbstractController
{
    /**
     * Page de connexion.
     * Si tu es déjà connecté, ouste, direction l'accueil !
     * Sinon, affiche le formulaire de login et les erreurs s'il y en a (mauvais mot de passe, etc.).
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_accueil');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * Déconnexion.
     * Cette méthode ne sera jamais exécutée car symfony l'intercepte avant.
     * Mais elle doit exister pour que la route soit valide. C'est un leurre !
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
