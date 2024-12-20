<?php 
namespace App\Controller;

use Symfony\Bundle\SecurityBundle\Security; // Attention à bien importer cette classe
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $user = $this->security->getUser();

        if ($user) {
            // Logique de redirection basée sur les rôles
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->redirectToRoute('admin_dashboard');
            } elseif (in_array('ROLE_BOUTIQUIER', $user->getRoles())) {
                return $this->redirect('http://127.0.0.1:8000/boutiquier/clients');
            } elseif (in_array('ROLE_CLIENT', $user->getRoles())) {
                return $this->redirectToRoute('client_dettes');
            }
        }

        // Page de connexion par défaut
        return $this->render('yes/login.html.twig');
    }
}
