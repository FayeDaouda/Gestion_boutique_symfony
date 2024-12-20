<?php
// src/Repository/DemandeRepository.php
namespace App\Repository;

use App\Entity\Demande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Article;


class DemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Demande::class);
    }

    public function findDemandesWithArticles()
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.article', 'a') // Jointure avec l'entité Article
            ->addSelect('a') // Sélectionne les articles associés
            ->getQuery()
            ->getResult();
    }
}
