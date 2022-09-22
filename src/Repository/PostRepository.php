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


    public function findArticlesByOrder($value, $user, $order)
    {
        return $this->createQueryBuilder('p')
        ->orderBy('p.'.$value, $order)
        ->andWhere('p.user = :val')
        ->andWhere("p.published = true")
        ->setParameter('val', $user)
        ->getQuery()
        ->getResult();
    }
      
    public function findByRateAverage( $order, $user = null)
    {
        $qb =  $this->createQueryBuilder('p')
        ->leftJoin('p.rates', 'rates') ;     
        if($user){
            $qb->andWhere('p.user = :val')
            ->setParameter('val', $user);
        }
        $qb->andWhere("p.published = true")
        ->orderBy("rates.value", $order);
        return $qb->getQuery()
        ->getResult();
    }
    
    public function findBySport($sport)
    {
        return $this->createQueryBuilder('p')
        ->andWhere('p.category = :val')
        ->setParameter('val', $sport)
        ->orderBy('p.created_At', 'DESC')
        ->getQuery()
        ->getResult();
    }
    
    public function findByUserDesc($user)
    {
        return $this->createQueryBuilder('p')
        ->andWhere('p.user = :val')
        ->andWhere("p.published = true")
        ->setParameter('val', $user)
        ->orderBy('p.created_At', 'DESC')
        ->getQuery()
        ->getResult();
    }

    public function findByFilters($author = null, $value = "created_At" ,$order = "DESC")
    {
        $qb =  $this->createQueryBuilder('p')
        ->andWhere("p.published = true");
        if($author){
            $qb->andWhere('p.user = :val')
            ->setParameter('val', $author);
        }
        $qb->orderBy('p.'.$value, $order);

        return $qb        
        ->getQuery()
        ->getResult();
    }  
    
    public function findByFilterandCategory($search, $sport = null)
    {
        $qb = $this->createQueryBuilder('p')
        ->orderBy('p.created_At', 'DESC')
        ->andWhere("p.description LIKE :search OR p.title LIKE :search")
        ->setParameter('search', "%{$search}%");
        if ($sport){
            $qb
            ->andWhere("p.category = :sport")
            ->setParameter("sport", $sport);
        }
        return $qb->getQuery()
        ->getResult();
    }

    public function findOneByPrimary($sport)
    {
        $qb = $this->createQueryBuilder('p')
        ->where('p.important = true')
        ->andWhere("p.published = true");
        if($sport != null){
            $qb->andWhere('p.category = :val')
            ->setParameter('val', $sport);
        };
        return $qb->orderBy('p.created_At', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getResult();
    }

    public function findPostsExceptPrimary($id, $sport, $offset = 0)
    {

        $qb =  $this->createQueryBuilder('p')
        ->where('p.id NOT LIKE :id ')
        ->andWhere("p.published = true")
        ->setParameter('id', $id)
        ->orderBy('p.created_At', 'DESC');
        if($sport != null){
            $qb->andWhere('p.category = :val')
            ->setParameter('val', $sport);
        }
        
        return $qb->setFirstResult($offset)
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();

    }
}



    




    

