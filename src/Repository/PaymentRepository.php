<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Payment;
use App\Entity\PaymentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
final class PaymentRepository extends ServiceEntityRepository
{
    private const SUM_AMOUNT = 'COALESCE(SUM(p.amountCents), 0)';
    private const WHERE_STATUS = 'p.status = :s';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findOneByStripeSessionId(string $sessionId): ?Payment
    {
        return $this->findOneBy(['stripeSessionId' => $sessionId]);
    }

    /**
     * CA total encaissé (paiements réussis), en centimes.
     */
    public function totalRevenueCents(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select(self::SUM_AMOUNT)
            ->where(self::WHERE_STATUS)
            ->setParameter('s', PaymentStatus::Succeeded)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * CA encaissé sur un intervalle [from, to[, en centimes.
     */
    public function revenueBetweenCents(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select(self::SUM_AMOUNT)
            ->where(self::WHERE_STATUS)
            ->andWhere('p.createdAt >= :from')
            ->andWhere('p.createdAt < :to')
            ->setParameter('s', PaymentStatus::Succeeded)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Nombre de ventes (paiements réussis).
     */
    public function countSales(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where(self::WHERE_STATUS)
            ->setParameter('s', PaymentStatus::Succeeded)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Total remboursé, en centimes.
     */
    public function refundedTotalCents(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select(self::SUM_AMOUNT)
            ->where(self::WHERE_STATUS)
            ->setParameter('s', PaymentStatus::Refunded)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * CA et ventes par formation (paiements réussis), trié par CA décroissant.
     *
     * @return list<array{title: string, revenue: int, sales: int}>
     */
    public function revenueByFormation(): array
    {
        /** @var list<array{title: string, revenue: string|int, sales: string|int}> $rows */
        $rows = $this->createQueryBuilder('p')
            ->select('f.title AS title', 'COALESCE(SUM(p.amountCents), 0) AS revenue', 'COUNT(p.id) AS sales')
            ->join('p.formation', 'f')
            ->where(self::WHERE_STATUS)
            ->setParameter('s', PaymentStatus::Succeeded)
            ->groupBy('f.id')
            ->orderBy('revenue', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (array $r): array => [
                'title'   => (string) $r['title'],
                'revenue' => (int) $r['revenue'],
                'sales'   => (int) $r['sales'],
            ],
            $rows,
        );
    }
}
