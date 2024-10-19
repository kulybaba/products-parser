<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findBySlugs(array $slugs): array
    {
        $products = $this->createQueryBuilder('p')
            ->andWhere('p.slug IN (:slugs)')
            ->setParameter('slugs', $slugs)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $result[$product->getSlug()] = $product;
        }

        return $result;
    }
}
