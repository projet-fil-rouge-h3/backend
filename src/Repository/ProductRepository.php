<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    /**
     * Récupère les produits formatés pour le Front-end (PageResponse)
     * $includeInactive = true pour le back-office (produits désactivés inclus)
     */
    public function getPaginatedProducts(int $page, int $size, ?int $categoryId = null, ?string $search = null, bool $includeInactive = false): array
    {
        //  On prépare la requête SQL
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.displayPriority', 'ASC'); // Le fameux tri par priorité !

        //  Le catalogue public ne montre que les produits actifs
        if (!$includeInactive) {
            $qb->andWhere('p.isActive = :active')
                ->setParameter('active', true);
        }

        //  Filtre par catégorie si demandé
        if ($categoryId) {
            $qb->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        //  Filtre par recherche texte (q)
        if ($search) {
            $qb->andWhere('p.name LIKE :search OR p.shortDescription LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // pagination
        $query = $qb->getQuery()
            ->setFirstResult($page * $size) // Offset (à partir de quel résultat on commence)
            ->setMaxResults($size);         // Limite par page

        $paginator = new Paginator($query);
        $totalElements = count($paginator);

        return [
            'content' => iterator_to_array($paginator),
            'totalElements' => $totalElements,
            'totalPages' => (int) ceil($totalElements / $size), // ceil() renvoie un float → JSON "1.0" sans le cast
            'number' => $page,
            'size' => $size,
        ];
    }
}
