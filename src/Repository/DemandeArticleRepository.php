<?php

// src/Repository/DemandeArticleRepository.php

namespace App\Repository;

use App\Entity\DemandeArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DemandeArticle|null find($id, $lockMode = null, $lockVersion = null)
 * @method DemandeArticle|null findOneBy(array $criteria, array $orderBy = null)
 * @method DemandeArticle[]    findAll()
 * @method DemandeArticle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DemandeArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeArticle::class);
    }

    // Ajoutez des méthodes personnalisées ici
}
