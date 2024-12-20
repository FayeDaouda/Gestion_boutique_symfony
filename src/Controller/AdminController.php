<?php
// AdminController.php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ArticleRepository;
use App\Repository\ClientRepository;
use App\Repository\DetteRepository;
use App\Entity\Article;
use App\Entity\Client;


use App\Form\ArticleType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        ArticleRepository $articleRepository,
        DetteRepository $detteRepository,
        Request $request
    ): Response {
        // Récupération du rôle à filtrer s'il existe
        $role = $request->query->get('role', null); // Récupère le rôle à filtrer s'il existe
        $page = $request->query->getInt('page', 1);  // Numéro de la page, défaut à 1
        $limit = 10;  // Nombre d'éléments par page
        $offset = ($page - 1) * $limit;

        // Pagination des utilisateurs
        $queryBuilder = $userRepository->createQueryBuilder('u');

        // Filtrage par rôle si spécifié
        if ($role) {
            $queryBuilder->where('u.roles LIKE :role')
                ->setParameter('role', '%"'.$role.'"%');
        }

        // Pagination
        $queryBuilder->orderBy('u.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $users = $queryBuilder->getQuery()->getResult();

        // Calcul du nombre total d'utilisateurs pour déterminer le nombre total de pages
        $totalUsersQuery = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        $totalPagesUsers = ceil($totalUsersQuery / $limit);

        // Récupération des articles sans pagination
        $articles = $articleRepository->findAll();

        // Récupération des dettes sans pagination
        $dettes = $detteRepository->findAll();

        // Transmission des données à la vue
        return $this->render('admin/index.html.twig', [
            'users' => $users,
            'articles' => $articles,
            'dettes' => $dettes,
            'selected_role' => $role,
            'currentPage' => $page,
            'totalPagesUsers' => $totalPagesUsers,
        ]);
    }





    
    #[Route('/ajouter/utilisateur', name: 'ajouter_utilisateur')]

    #[Route('/user/add', name: 'user_add', methods: ['POST'])]
public function addUser(Request $request, UserRepository $userRepository, ClientRepository $clientRepository): Response
{
    // Récupérer les données du formulaire
    $data = $request->request->all();
    
    // Création d'un nouvel utilisateur
    $user = new User();
    $user->setLogin($data['login']);
    $user->setEmail($data['email']);
    $user->setRoles([$data['roles']]);
    $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT)); // Assurez-vous d'utiliser un encoder de mot de passe Symfony.

    // Enregistrement
    // Enregistrement de l'utilisateur
    $userRepository->saveUser($user);

    // Lier à un client si nécessaire
    if (!empty($data['prenom'])) {
        $client = new Client();
        $client->setUserAccount($user);
        $client->setSurname($data['prenom']);
        $client->setTelephone($data['tel']);
        $client->setAdresse($data['adresse']);
        $clientRepository->save($client, true);
    }

    // Redirection vers l'URL spécifique (http://127.0.0.1:8000/admin)
        return $this->redirect('http://127.0.0.1:8000/admin');
}


    #[Route('/modifier/utilisateur/{id}', name: 'modifier_utilisateur')]
    public function editUser(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user); // Formulaire de modification utilisateur

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour de l'utilisateur dans la base de données
            $this->getDoctrine()->getManager()->flush();

            // Message de confirmation
            $this->addFlash('success', 'Utilisateur modifié avec succès.');

            return $this->redirectToRoute('user_list'); // Redirection vers la liste des utilisateurs
        }

        return $this->render('user/edit_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/admin/articles', name: 'admin_articles_list')]
    public function listArticles(ArticleRepository $articleRepository, Request $request): Response
    {
        // Paramètres de pagination
        $page = (int) $request->query->get('page', 1); // Page actuelle, 1 par défaut
        $limit = 8; // Nombre d'articles par page

        // Calcul de l'offset
        $offset = ($page - 1) * $limit;

        // Récupération des articles avec pagination
        $articles = $articleRepository->findBy([], null, $limit, $offset);

        // Récupérer le total des articles pour les liens de pagination
        $totalArticles = $articleRepository->count([]);

        // Nombre total de pages
        $totalPages = ceil($totalArticles / $limit);

        return $this->render('admin/list.html.twig', [
            'articles' => $articles,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }


      // Route pour afficher la liste des utilisateurs
      #[Route('/admin/users', name: 'admin_users_list')]
      public function listUsers(UserRepository $userRepository, Request $request): Response
      {
          // Paramètres de pagination
          $page = (int) $request->query->get('page', 1); // Page actuelle, 1 par défaut
          $limit = 8; // Nombre d'utilisateurs par page
  
          // Calcul de l'offset
          $offset = ($page - 1) * $limit;
  
          // Récupération des utilisateurs avec pagination
          $users = $userRepository->findBy([], null, $limit, $offset);
  
          // Récupérer le total des utilisateurs pour les liens de pagination
          $totalUsers = $userRepository->count([]);
  
          // Nombre total de pages
          $totalPages = ceil($totalUsers / $limit);
  
          // Générer les liens de pagination
          $pagination = $this->generatePagination($page, $totalPages, 'admin_users_list');
  
          // Passer les variables à la vue
          return $this->render('admin/index.html.twig', [
              'users' => $users,
              'pagination' => $pagination, // Assurez-vous de passer cette variable
              'currentPage' => $page,
              'totalPages' => $totalPages,
          ]);
      }
  
      // Fonction générant les liens de pagination
      private function generatePagination(int $currentPage, int $totalPages, string $routeName): array
      {
          $pagination = [];
  
          // Si la page actuelle est supérieure à 1, ajouter le lien "Précédent"
          if ($currentPage > 1) {
              $pagination['previous'] = $this->generateUrl($routeName, ['page' => $currentPage - 1]);
          }
  
          // Ajouter les liens pour chaque page
          $pagination['pages'] = [];
          for ($i = 1; $i <= $totalPages; $i++) {
              $pagination['pages'][] = [
                  'page' => $i,
                  'url' => $this->generateUrl($routeName, ['page' => $i]),
                  'active' => $i == $currentPage,
              ];
          }
  
          // Si la page actuelle est inférieure au total des pages, ajouter le lien "Suivant"
          if ($currentPage < $totalPages) {
              $pagination['next'] = $this->generateUrl($routeName, ['page' => $currentPage + 1]);
          }
  
          return $pagination;
      }
   



    #[Route('/admin/articles/disponibles', name: 'admin_articles_disponibles')]
    public function listAvailableArticles(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAvailableArticles(); // Articles disponibles
        return $this->render('admin/articles_disponibles.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/admin/articles/{id}/update', name: 'admin_article_update', methods: ['POST'])]
public function updateStock(Request $request, Article $article, EntityManagerInterface $entityManager): Response
{
    // Récupérer la nouvelle quantité saisie
    $nouvelleQuantite = $request->request->get('quantiteStock');

    // Validation : Vérifier que la quantité est un entier valide
    if (is_numeric($nouvelleQuantite) && (int)$nouvelleQuantite >= 0) {
        $article->setQuantiteStock((int)$nouvelleQuantite);

        // Persister les changements
        $entityManager->flush();

        // Ajouter un message de succès
        $this->addFlash('success', 'Quantité mise à jour avec succès.');
    } else {
        // Ajouter un message d'erreur en cas de validation échouée
        $this->addFlash('error', 'Quantité invalide. Veuillez saisir une valeur positive.');
    }

    // Rediriger vers la liste des articles
    return $this->redirectToRoute('admin_articles_list');
}


    
    #[Route('/admin/article/create', name: 'admin_article_create', methods: ['POST'])]
    public function createArticle(Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer les données du formulaire
        $nom = $request->request->get('nom'); // Correspond au champ 'name="nom"' dans le formulaire
        $quantiteStock = $request->request->get('quantite_stock'); // Correspond au champ 'name="quantite_stock"'
        $prix = $request->request->get('prix'); // Correspond au champ 'name="prix"'
    
        // Validation des données (optionnel)
        if (empty($nom) || empty($quantiteStock) || empty($prix)) {
            $this->addFlash('error', 'Tous les champs sont obligatoires.');
            return $this->redirectToRoute('admin_articles'); // Rediriger vers la liste des articles
        }
    
        // Créer un nouvel article
        $article = new Article();
        $article->setNom($nom);
        $article->setQuantiteStock((int)$quantiteStock);
        $article->setPrix((float)$prix);
    
        // Sauvegarder dans la base de données
        $em->persist($article);
        $em->flush();
    
        // Ajouter un message de succès
        $this->addFlash('success', 'Article ajouté avec succès !');
    
        // Rediriger vers l'URL spécifique
return $this->redirect('http://127.0.0.1:8000/admin/articles');
    }

    #[Route('/admin/user/toggle-status/{id}', name: 'toggle_user_status', methods: ['PATCH'])]
    public function toggleStatus($id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Trouver l'utilisateur avec l'ID donné
            $user = $userRepository->find($id);
    
            if (!$user) {
                return new JsonResponse(['status' => 'error', 'message' => 'Utilisateur non trouvé.'], 404);
            }
    
            // Récupérer le statut actuel depuis la base de données
            $currentStatus = $user->getIsActive();
    
            // Inverser l'état actuel de is_active
            $newStatus = !$currentStatus; // Inverse l'état actuel
    
            // Met à jour le statut de l'utilisateur
            $user->setIsActive($newStatus);
    
            // Utilisation de l'EntityManager pour sauvegarder l'entité
            $entityManager->persist($user); // Persister l'entité
            $entityManager->flush(); // Effectuer le flush dans la base de données
    
            return new JsonResponse([
                'status' => 'success',
                'message' => $newStatus ? 'Utilisateur activé avec succès.' : 'Utilisateur désactivé avec succès.',
            ]);
        } catch (\Exception $e) {
            // Si une exception se produit, retourne un message d'erreur détaillé
            return new JsonResponse(['status' => 'error', 'message' => 'Une erreur est survenue : ' . $e->getMessage()], 500);
        }
    }

    
}
