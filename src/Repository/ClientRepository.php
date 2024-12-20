<?php
// ClientRepository.php
namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function save(Client $client, bool $flush = false)
    {
        $entityManager = $this->getEntityManager(); // Utilisez getEntityManager() pour obtenir l'EntityManager
        $entityManager->persist($client);
        if ($flush) {
            $entityManager->flush();
        }
    }
}
