<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Lesson;
use App\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Resource>
 */
final class ResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resource::class);
    }

    /**
     * @return Resource[]
     */
    public function findByLessonOrdered(Lesson $lesson): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.lesson = :lesson')
            ->setParameter('lesson', $lesson)
            ->orderBy('r.displayOrder', 'ASC')
            ->addOrderBy('r.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
