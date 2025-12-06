<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de base pour la page d'accueil.
 * C'est la première chose que voient les visiteurs.
 * Simple, efficace, c'est la porte d'entrée.
 */
final class BaseController extends AbstractController
{
    /**
     * Affiche la page d'accueil.
     * Rien de fou ici, juste le template de base.
     */
    #[Route('/', name: 'app_accueil')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', []);
    }
}
