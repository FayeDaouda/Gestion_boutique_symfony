<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Retourne les articles avec une quantité en stock > 0
     *
     * @return Article[]
     */
    public function findAvailableArticles(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.quantiteStock > 0')
            ->getQuery()
            ->getResult();
    }
}
