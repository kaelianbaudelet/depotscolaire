<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Le repository pour les utilisateurs.
 * C'est ici qu'on fait les requêtes pour trouver des users, ou pour gérer leurs mots de passe.
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Met à jour le mot de passe hashé d'un utilisateur.
     * C'est utilisé automatiquement par Symfony pour moderniser les hashs si l'algo change.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Compte le nombre total d'étudiants (utilisateurs n'ayant pas le rôle ROLE_TEACHER ou ROLE_ADMIN implicitement,
     * mais pour faire simple ici on compte tout le monde ou on filtre sur ceux qui n'ont pas de rôle particulier si besoin).
     * Ici on va compter tout le monde pour l'exemple "Global".
     */
    public function countStudents(): int
    {
        return $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les X derniers utilisateurs inscrits.
     * @param int $limit
     * @return User[]
     */
    public function findLatestUsers(int $limit = 5): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les utilisateurs ayant un rôle spécifique.
     * Note: Les rôles sont stockés en JSON, donc on utilise LIKE.
     */
    public function countUsersByRole(string $role): int
    {
        return $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les nouveaux inscrits du mois en cours.
     */
    public function countNewUsersThisMonth(): int
    {
        $start = new \DateTimeImmutable('first day of this month 00:00:00');
        $end = new \DateTimeImmutable('last day of this month 23:59:59');

        return $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAllUsers(): int
    {
        return $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
