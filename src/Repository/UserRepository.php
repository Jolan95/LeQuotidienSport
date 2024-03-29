<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }


    public function findByRoles($value, $offset)
    {
        $qb =  $this->createQueryBuilder('u')
            ->andWhere('u.roles = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC');
            if($offset){
                $qb->setFirstResult($offset)
                ->setMaxResults(20);
            }
        return $qb
        ->getQuery()
        ->getResult();
    }

    public function findNumberPage($value)
    {
        $qb =  $this->createQueryBuilder('u')
            ->select('count(u)')
            ->andWhere('u.roles = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC');
        return $qb
        ->getQuery()
        ->getSingleScalarResult();
    }

    public function findAuthorAndAdmin()
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles = :admin')
            ->orWhere(`u.roles = :auth`)
            ->getQuery()
            ->getResult()
        ;
    }
}
