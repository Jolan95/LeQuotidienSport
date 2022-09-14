<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }


    public function findArticlesByDate()
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.created_At', 'DESC')
            ->getQuery()
            ->getResult()
            ;
        }
       
        
        public function findBySport($sport)
        {
            return $this->createQueryBuilder('p')
            ->andWhere('p.category = :val')
            ->setParameter('val', $sport)
            ->orderBy('p.created_At', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        }

        public function findOneByPrimary($sport = null)
        {
            if($sport == null){
                return $this->createQueryBuilder('p')
                ->where('p.important = true')
                ->orderBy('p.created_At', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getResult();
            } else {
                return $this->createQueryBuilder('p')
                ->where('p.important = true')
                ->andWhere('p.category = :val')
                ->setParameter('val', $sport)
                ->orderBy('p.created_At', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getResult();

            }
        ;
        }

    public function findPostsExceptPrimary($id, $sport = null)
    {
        if($sport == null){
            return $this->createQueryBuilder('p')
            ->where('p.id NOT LIKE :id ')
            ->setParameter('id', $id)
            ->orderBy('p.created_At', 'DESC')
            ->getQuery()
            ->getResult();
        } else{
            return $this->createQueryBuilder('p')
            ->where('p.id NOT LIKE :id ')
            ->andWhere('p.category = :val')
            ->setParameter('id', $id)
            ->setParameter('val', $sport)
            ->orderBy('p.created_At', 'DESC')
            ->getQuery()
            ->getResult();
        }
    }
}



    

