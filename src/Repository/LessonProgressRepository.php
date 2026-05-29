<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LessonProgress>
 */
final class LessonProgressRepository extends ServiceEntityRepository
{
    private const ENROLLMENT_FILTER = 'p.enrollment = :enrollment';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LessonProgress::class);
    }

    public function findOneByEnrollmentAndLesson(Enrollment $enrollment, Lesson $lesson): ?LessonProgress
    {
        return $this->createQueryBuilder('p')
            ->andWhere(self::ENROLLMENT_FILTER)
            ->andWhere('p.lesson = :lesson')
            ->setParameter('enrollment', $enrollment)
            ->setParameter('lesson', $lesson)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return LessonProgress[]
     */
    public function findByEnrollment(Enrollment $enrollment): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere(self::ENROLLMENT_FILTER)
            ->setParameter('enrollment', $enrollment)
            ->orderBy('p.lastWatchedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countCompletedByEnrollment(Enrollment $enrollment): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere(self::ENROLLMENT_FILTER)
            ->andWhere('p.completedAt IS NOT NULL')
            ->setParameter('enrollment', $enrollment)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
