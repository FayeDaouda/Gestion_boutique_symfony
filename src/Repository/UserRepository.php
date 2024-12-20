<?php

// UserRepository.php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }



    public function findActiveUsers()
{
    return $this->createQueryBuilder('u')
        ->andWhere('u.isActive = :active')
        ->setParameter('active', true)
        ->getQuery()
        ->getResult();
}

public function findByRole($role)
{
    return $this->createQueryBuilder('u')
        ->andWhere('u.roles LIKE :role')
        ->setParameter('role', '%' . $role . '%')
        ->getQuery()
        ->getResult();
}

public function saveUser($user)
{
    // Récupérer l'EntityManager
    $entityManager = $this->getEntityManager();

    // Persister l'entité
    $entityManager->persist($user);

    // Enregistrer les modifications dans la base de données
    $entityManager->flush();
}
public function remove(User $user, bool $flush = false)
{
    $this->_em->remove($user);
    if ($flush) {
        $this->_em->flush();
    }
}

}

