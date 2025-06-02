<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.event = :event')
            ->setParameter('event', $event)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingOrders(\DateTimeInterface $olderThan = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', Order::STATUS_PENDING);

        if ($olderThan) {
            $qb->andWhere('o.createdAt < :olderThan')
                ->setParameter('olderThan', $olderThan);
        }

        return $qb->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getOrderStatistics(Event $event): array
    {
        $result = $this->createQueryBuilder('o')
            ->select([
                'COUNT(o.id) as total_orders',
                'SUM(o.totalAmount) as total_revenue',
                'AVG(o.totalAmount) as average_order_value'
            ])
            ->where('o.event = :event')
            ->andWhere('o.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', Order::STATUS_PAID)
            ->getQuery()
            ->getSingleResult();

        return [
            'total_orders' => (int)($result['total_orders'] ?? 0),
            'total_revenue' => (float)($result['total_revenue'] ?? 0),
            'average_order_value' => (float)($result['average_order_value'] ?? 0)
        ];
    }
}