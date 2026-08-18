<?php

namespace App\Repository;

use App\Entity\ClothingItem;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClothingItem>
 */
final class ClothingItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClothingItem::class);
    }


    public function findByUserAndMetaData(User $user): array
    {   
        $qb = $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.metaData IS NOT NULL')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }
}
