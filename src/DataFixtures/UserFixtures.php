<?php
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    // Injection du service UserPasswordHasherInterface dans le constructeur
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $userAdmin = new User();
        $userAdmin->setEmail('admin@example.com');
        $userAdmin->setLogin('admin');
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $userAdmin->setPassword($this->passwordHasher->hashPassword($userAdmin, 'admin123'));

        $userBoutiquier = new User();
        $userBoutiquier->setEmail('boutiquier@example.com');
        $userBoutiquier->setLogin('boutiquier');
        $userBoutiquier->setRoles(['ROLE_BOUTIQUIER']);
        $userBoutiquier->setPassword($this->passwordHasher->hashPassword($userBoutiquier, 'boutiquier123'));

        $userClient = new User();
        $userClient->setEmail('client@example.com');
        $userClient->setLogin('client');
        $userClient->setRoles(['ROLE_CLIENT']);
        $userClient->setPassword($this->passwordHasher->hashPassword($userClient, 'client123'));

        $manager->persist($userAdmin);
        $manager->persist($userBoutiquier);
        $manager->persist($userClient);

        $manager->flush();
    }
}
