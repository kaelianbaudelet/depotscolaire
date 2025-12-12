<?php

namespace App\Repository;

use App\Entity\Classroom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Le gestionnaire des classes en BDD.
 *
 * @extends ServiceEntityRepository<Classroom>
 *
 * @method Classroom|null find($id, $lockMode = null, $lockVersion = null)
 * @method Classroom|null findOneBy(array $criteria, array $orderBy = null)
 * @method Classroom[]    findAll()
 * @method Classroom[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClassroomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classroom::class);
    }

//    /**
//     * @return Classroom[] Returns an array of Classroom objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Classroom
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * Récupère des statistiques pour une classe donnée (Nombre d'élèves, Nombre de devoirs).
     * Utilise des jointures et des agrégations.
     */
    public function getClassroomStats(int $classroomId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // On le fait en DQL / QueryBuilder pour l'exercice, mais notez que pour compter deux relations OneToMany distinctes
        // dans la même requête sans multiplier les résultats (produit cartésien), c'est parfois tricky avec Doctrine pure.
        // Option 1 : Faire 2 requêtes séparées (plus propre souvent).
        // Option 2 : COUNT(DISTINCT x).
        
        return $this->createQueryBuilder('c')
            ->select('c.name as className', 'COUNT(DISTINCT s.id) as studentCount', 'COUNT(DISTINCT a.id) as assignmentCount')
            ->leftJoin('c.students', 's')
            ->leftJoin('c.assignments', 'a')
            ->where('c.id = :id')
            ->setParameter('id', $classroomId)
            ->groupBy('c.id')
            ->getQuery()
            ->getSingleResult();
    }
}
