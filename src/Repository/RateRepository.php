<?php

namespace App\Repository;

use App\Entity\Rate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Rate|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rate|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rate[]    findAll()
 * @method Rate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rate::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Rate $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Rate $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
    
    public function findAverageRating($post):array
    {
        return $this->createQueryBuilder('r')
            ->select("AVG(r.value)")
            ->andWhere('r.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getOneOrNullResult();
        ;
    }
    
    public function findAverageRatingbyPost($post):array
    {
        return $this->createQueryBuilder('r')
            ->select("AVG(r.value)")
            ->andWhere('r.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getOneOrNullResult();
        ;
    }
    
}
