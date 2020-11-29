<?php

namespace App\Repository;

use App\Entity\Date;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraints\Country;

/**
 * @method Date|null find($id, $lockMode = null, $lockVersion = null)
 * @method Date|null findOneBy(array $criteria, array $orderBy = null)
 * @method Date[]    findAll()
 * @method Date[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Date::class);
    }

//    /**
//    * @return Date[] Returns an array of Date objects
//    */

    public function findByCountry($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.country = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(100)
            ->getQuery()
            ->getScalarResult()
        ;
    }

    public function findByYear($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.year = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(100)
            ->getQuery()
            ->getScalarResult()
            ;
    }

    public function findByTwoParameters($country, $year)
    {
        return $this->createQueryBuilder('d' )
            ->andWhere('d.year = :val', 'd.country = :index')
            ->setParameter('val', $year)
            ->setParameter('index', $country)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(100)
            ->getQuery()
            ->getScalarResult()
            ;
    }

}
