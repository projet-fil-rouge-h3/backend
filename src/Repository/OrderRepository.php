<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Toutes les commandes, paginées, pour le back-office (format PageResponse).
     *
     * @return array{content: Order[], totalElements: int, totalPages: int, number: int, size: int}
     */
    public function getPaginatedOrders(int $page, int $size): array
    {
        $query = $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->setFirstResult($page * $size)
            ->setMaxResults($size);

        $paginator = new Paginator($query);
        $totalElements = count($paginator);

        return [
            'content' => iterator_to_array($paginator),
            'totalElements' => $totalElements,
            'totalPages' => (int) ceil($totalElements / $size),
            'number' => $page,
            'size' => $size,
        ];
    }

    /**
     * Chiffre d'affaires HT total (somme des factures émises).
     */
    public function getTotalRevenueHt(): float
    {
        return (float) $this->getEntityManager()->createQuery(
            'SELECT COALESCE(SUM(i.amountHt), 0) FROM App\Entity\Invoice i'
        )->getSingleScalarResult();
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Order
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
