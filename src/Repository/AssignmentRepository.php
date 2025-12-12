<?php

namespace App\Repository;

use App\Entity\Assignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Pour gérer les devoirs en base de données.
 * Trouver un devoir, en lister plusieurs... c'est ici que ça se passe.
 *
 * @extends ServiceEntityRepository<Assignment>
 *
 * @method Assignment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Assignment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Assignment[]    findAll()
 * @method Assignment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assignment::class);
    }

//    /**
//     * @return Assignment[] Returns an array of Assignment objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Assignment
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * Récupère des stats sur les devoirs rendus vs total attendu potentiellement.
     * Ici on va simplement compter le nombre de rendus par devoir, trié par le plus rendu.
     */
    public function getSubmissionStats(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.title', 'COUNT(s.id) as submissionCount')
            ->leftJoin('a.submissions', 's')
            ->groupBy('a.id')
            ->orderBy('submissionCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function countTotalAssignments(): int
    {
        return $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTotalSubmissions(): int
    {
        // On doit joindre ou compter depuis SubmissionRepository, mais on peut le faire ici si on veut tout dans un service,
        // ou simplement une requête directe sur l'entité Submission si on avait accès, mais ici on est dans AssignmentRepository.
        // On peut compter les submissions via la jointure, mais le plus simple est d'injecter SubmissionRepository ailleurs.
        // MAIS, comme on veut compter TOUTES les submissions, on peut le faire ici via l'entity manager ou une jointure 'inutile' mais valide.
        // Mieux vaut une requête propre:
        return $this->getEntityManager()->createQueryBuilder()
            ->select('count(s.id)')
            ->from('App\Entity\Submission', 's')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
