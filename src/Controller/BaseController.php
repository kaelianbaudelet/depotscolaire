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
    public function index(\App\Repository\UserRepository $userRepository, \App\Repository\AssignmentRepository $assignmentRepository): Response
    {
        // Récupération des statistiques globales
        
        // 1. Nombre de Professeurs
        $teacherCount = $userRepository->countUsersByRole('ROLE_TEACHER');

        // 2. Nombre d'Élèves (On utilise la même logique que l'admin pour être cohérent)
        // Mais pour l'accueil, on peut aussi simplement compter les utilisateurs avec ROLE_USER (excluant profs/admins si possible, ou simple countAllUsers - profs)
        // On va utiliser countAllUsers - TeacherCount - AdminCount pour être précis si on a ces méthodes,
        // Ou plus simplement si on veut juste "Inscrits" on peut mettre le total.
        // La demande est "nombre d'élèves", donc on va tenter d'être juste.
        $totalUsers = $userRepository->countAllUsers();
        $adminCount = $userRepository->countUsersByRole('ROLE_ADMIN');
        $studentCount = $totalUsers - $teacherCount - $adminCount;
        if ($studentCount < 0) $studentCount = 0;

        // 3. Nombre de Devoirs
        $assignmentCount = $assignmentRepository->countTotalAssignments();

        return $this->render('home/index.html.twig', [
            'teacher_count' => $teacherCount,
            'student_count' => $studentCount,
            'assignment_count' => $assignmentCount,
        ]);
    }
}
