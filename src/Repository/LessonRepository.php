<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Lesson;
use App\Entity\Module;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lesson>
 */
final class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    /**
     * @return Lesson[]
     */
    public function findByModuleOrdered(Module $module): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.module = :module')
            ->setParameter('module', $module)
            ->orderBy('l.displayOrder', 'ASC')
            ->addOrderBy('l.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
