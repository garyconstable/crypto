<?php

namespace App\Repository;

use App\Entity\Rates;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Rates|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rates|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rates[]    findAll()
 * @method Rates[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RatesRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Rates::class);
    }

    // /**
    //  * @return Rates[] Returns an array of Rates objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Rates
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
