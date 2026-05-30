<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\EnrollmentSource;
use App\Entity\Formation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enrollment>
 */
final class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    /**
     * @return Enrollment[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.createdAt', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndFormation(User $user, Formation $formation): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->andWhere('e.formation = :formation')
            ->setParameter('user', $user)
            ->setParameter('formation', $formation)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countBySource(EnrollmentSource $source): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.source = :source')
            ->setParameter('source', $source)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
