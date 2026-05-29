<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
final class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /**
     * @return Formation[]
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.published = true')
            ->orderBy('f.displayOrder', 'ASC')
            ->addOrderBy('f.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySlug(string $slug): ?Formation
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
