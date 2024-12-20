<?php
namespace App\Controller;

use App\Entity\Client;
use App\Repository\DemandeRepository;
use App\Form\ClientType;
use App\Entity\User;  // Importez correctement la classe User
use App\Entity\Dette;
use App\Entity\Article;
use App\Entity\Demande;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\DetteArticle;
use App\Repository\ClientRepository;
use App\Repository\ArticleRepository;
use App\Repository\DetteRepository;
use App\Entity\DemandeArticle;
use App\Form\DetteType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\ClientController;  // Importer le ClientController
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;





class BoutiquierController extends AbstractController
{
      
    #[Route('/boutiquier/clients', name: 'boutiquier_client_list', methods: ['GET'])]
public function index(EntityManagerInterface $entityManager): Response
{
    // Vérifier les permissions (ROLE_BOUTIQUIER)
    $this->denyAccessUnlessGranted('ROLE_BOUTIQUIER');

    // Récupérer tous les clients
    $clients = $entityManager->getRepository(Client::class)->findAll();

    // Vérifier s'il y a des clients dans la base de données
    if (!$clients) {
        $this->addFlash('warning', 'Aucun client trouvé dans le système.');
    }

    // Récupérer tous les utilisateurs
    $users = $entityManager->getRepository(User::class)->findAll();

    // Récupérer tous les articles
    $articles = $entityManager->getRepository(Article::class)->findAll();

    // Récupérer toutes les demandes
    $demandes = $entityManager->getRepository(Demande::class)->findAll();

    // Préparer la liste des clients avec leurs dettes et le montant restant
    $clientsWithDebt = [];
    foreach ($clients as $client) {
        // Assurez-vous que la méthode `getTotalMontantRestant` existe dans l'entité `Client`
        $totalMontantRestant = $client->getTotalMontantRestant();

        // Récupérer les dettes du client
        $dettes = $client->getDettes(); // Assurez-vous que la méthode getDettes existe

        $clientsWithDebt[] = [
            'client' => $client,
            'montantRestant' => $totalMontantRestant,
            'dettes' => $dettes,
        ];
    }

    // Rendre la vue Twig avec les données
    return $this->render('boutiquier/index.html.twig', [
        'clientsWithDebt' => $clientsWithDebt,
        'users' => $users,
        'articles' => $articles,
        'demandes' => $demandes,
    ]);
}


    
    #[Route('/boutiquier/client/create', name: 'boutiquier_client_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Vérifier les permissions (ROLE_BOUTIQUIER)
        $this->denyAccessUnlessGranted('ROLE_BOUTIQUIER');
    
        // Récupérer les données du formulaire
        $formData = $request->request->all();
        $surname = $formData['surname'] ?? null;
        $telephone = $formData['telephone'] ?? null;
        $adresse = $formData['adresse'] ?? null;
    
        $createUserAccount = $formData['create_user_account'] ?? false;
        $email = $formData['email'] ?? null;
        $login = $formData['login'] ?? null;
        $password = $formData['password'] ?? null;
    
        // Vérifier que les champs obligatoires du client sont remplis
        if (!$surname || !$telephone) {
            $this->addFlash('error', 'Les champs Nom et Téléphone sont obligatoires.');
            return $this->redirectToRoute('boutiquier_client_list');
        }
    
        // Vérification d'unicité pour le client
        $existingClientByName = $entityManager->getRepository(Client::class)->findOneBy(['surname' => $surname]);
        $existingClientByPhone = $entityManager->getRepository(Client::class)->findOneBy(['telephone' => $telephone]);
    
        if ($existingClientByName) {
            $this->addFlash('error', 'Un client avec ce nom existe déjà.');
            return $this->redirectToRoute('boutiquier_client_list');
        }
    
        if ($existingClientByPhone) {
            $this->addFlash('error', 'Un client avec ce téléphone existe déjà.');
            return $this->redirectToRoute('boutiquier_client_list');
        }
    
        // Créer une nouvelle instance de Client
        $client = new Client();
        $client->setSurname($surname);
        $client->setTelephone($telephone);
        $client->setAdresse($adresse);
    
        // Si l'option de création d'un compte utilisateur est activée
        if ($createUserAccount) {
            // Vérifier les champs requis pour l'utilisateur
            if (!$email || !$login || !$password) {
                $this->addFlash('error', 'Tous les champs utilisateur (Email, Login, Mot de Passe) sont obligatoires.');
                return $this->redirectToRoute('boutiquier_client_list');
            }
    
            // Vérification d'unicité pour l'email ou le login
            $existingUserByEmail = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            $existingUserByLogin = $entityManager->getRepository(User::class)->findOneBy(['login' => $login]);
    
            if ($existingUserByEmail) {
                $this->addFlash('error', 'Un utilisateur avec cet email existe déjà.');
                return $this->redirectToRoute('boutiquier_client_list');
            }
    
            if ($existingUserByLogin) {
                $this->addFlash('error', 'Un utilisateur avec ce login existe déjà.');
                return $this->redirectToRoute('boutiquier_client_list');
            }
    
            // Créer et configurer l'utilisateur
            $user = new User();
            $user->setEmail($email);
            $user->setLogin($login);
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_CLIENT']);
            $user->setIsActive(true);
    
            // Associer l'utilisateur au client
            $client->setUserAccount($user);
    
            // Persister l'utilisateur
            $entityManager->persist($user);
        }
    
        // Persister le client
        $entityManager->persist($client);
        $entityManager->flush();
    
        $this->addFlash('success', 'Le client et son compte utilisateur ont été créés avec succès.');
        return $this->redirectToRoute('boutiquier_client_list');
    }
    


    #[Route('/boutiquier/clients/{id}', name: 'boutiquier_client_show', methods: ['GET'])]
public function show(int $id, Request $request, EntityManagerInterface $entityManager): Response
{
    // Vérifier les permissions (ROLE_BOUTIQUIER)
    $this->denyAccessUnlessGranted('ROLE_BOUTIQUIER');

    // Récupérer le client par ID
    $client = $entityManager->getRepository(Client::class)->find($id);

    // Vérifier si le client existe
    if (!$client) {
        throw $this->createNotFoundException('Le client spécifié est introuvable.');
    }

    // Récupérer la valeur du filtre depuis la requête (par défaut : 'all')
    $filter = $request->query->get('filter', 'all');

    // Préparer la requête pour récupérer les dettes du client
    $queryBuilder = $entityManager->getRepository(Dette::class)->createQueryBuilder('d')
        ->where('d.client = :client')
        ->setParameter('client', $client);

    // Appliquer le filtre sur les dettes
    switch ($filter) {
        case 'solder':
            $queryBuilder->andWhere('d.montant = d.montantVerser');
            break;
        case 'non_solder':
            $queryBuilder->andWhere('d.montant != d.montantVerser');
            break;
        case 'all':
        default:
            // Pas de filtre supplémentaire
            break;
    }

    // Exécuter la requête pour récupérer les dettes
    $dettes = $queryBuilder->getQuery()->getResult();

    // Récupérer tous les articles
    $articles = $entityManager->getRepository(Article::class)->findAll();

    // Calculer les montants totaux
    $totalMontant = 0;
    $totalMontantVerser = 0;
    $totalMontantRestant = 0;

    foreach ($dettes as $dette) {
        $totalMontant += $dette->getMontant();
        $totalMontantVerser += $dette->getMontantVerser();
        $totalMontantRestant += ($dette->getMontant() - $dette->getMontantVerser());
    }

    // Retourner la vue avec les informations du client, des dettes filtrées et des articles
    return $this->render('boutiquier/show.html.twig', [
        'client' => $client,
        'dettes' => $dettes,
        'articles' => $articles,
        'totalMontant' => $totalMontant,
        'totalMontantVerser' => $totalMontantVerser,
        'totalMontantRestant' => $totalMontantRestant,
    ]);
}



    // Dans le contrôleur BoutiquierController
    #[Route('/dashboard', name: 'boutiquier_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        // Récupérer les clients
        $clients = $entityManager->getRepository(Client::class)->findAll();
        $users = $entityManager->getRepository(User::class)->findAll();
    
        // Calculer le montant restant pour chaque client (logique de dette)
        $clientsWithDebt = [];
        foreach ($clients as $client) {
            $totalMontantRestant = $client->getTotalMontantRestant(); // Assurez-vous que cette méthode existe et retourne le montant dû
            
            // Ajouter chaque client avec son montant restant
            if ($totalMontantRestant > 0) {
                $clientsWithDebt[] = [
                    'client' => $client,
                    'montantRestant' => $totalMontantRestant
                ];
            }
        }
    
        // Passer les données à la vue
        return $this->render('boutiquier/index.html.twig', [
            'clientsWithDebt' => $clientsWithDebt,
            'users' => $users, // Vous pouvez aussi passer d'autres variables comme 'users' si nécessaire
        ]);
 

    }
    
    #[Route('/demandes', name: 'demandes_list')]
    public function listDemandes(DemandeRepository $demandeRepository): Response
    {
        // Récupérer toutes les demandes avec les articles associés
        $demandes = $demandeRepository->findDemandesWithArticles();

        return $this->render('boutiquier/list.html.twig', [
            'demandes' => $demandes,
        ]);
    }

 

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
#[Route('/boutiquier/demandes', name: 'boutiquier_demande_list')]
public function listDemands(Request $request): Response
    {
        // Exemple d'utilisation de l'EntityManager pour récupérer les données
        $filter = $request->query->get('filter', 'all');
        
        if ($filter !== 'all') {
            $demandes = $this->entityManager
                ->getRepository(Demande::class)
                ->findBy(['etat' => $filter]);
        } else {
            $demandes = $this->entityManager
                ->getRepository(Demande::class)
                ->findAll();
        }

        return $this->render('boutiquier/list.html.twig', [
            'demandes' => $demandes,
        ]);
    }


    #[Route('/demandes/{id}/update', name: 'update_demande_state')]
public function updateDemandeState(Demande $demande, EntityManagerInterface $entityManager, Request $request): Response
{
    // Récupérer l'action de mise à jour (valider ou annuler)
    $action = $request->query->get('action');
    
    // Vérifier l'action et mettre à jour l'état
    if ($action === 'valider') {
        // Mettre à jour l'état de la demande
        $demande->setEtat('Validée');
        
        // Créer la dette correspondant à la demande validée
        $dette = new Dette();
        $dette->setMontant($demande->getMontant()); // Assurez-vous que la demande a un montant
        $dette->setClient($demande->getClient()); // Assurez-vous que la demande a un client associé
        $dette->setDate(new \DateTime()); // Date actuelle de création de la dette
        $dette->setMontantVerser(0); // Initialement aucun montant versé
        $dette->setMontantRestant($demande->getMontant()); // Le montant restant est égal au montant total

        // Persister la nouvelle dette dans la base de données
        $entityManager->persist($dette);
    } elseif ($action === 'annuler') {
        // Si l'action est d'annuler, on met l'état de la demande à "Annulée"
        $demande->setEtat('Annulée');
    }

    // Sauvegarder les changements de la demande et de la dette si validée
    $entityManager->flush();

    // Rediriger vers la liste des demandes
    return $this->redirectToRoute('demandes_list');
}


    #[Route('/demande/{id}/articles', name: 'demande_articles')]
    public function showArticles(int $id, DemandeRepository $demandeRepository, EntityManagerInterface $em): Response
{
    // Récupérer la demande
    $demande = $demandeRepository->find($id);
    
    // Vérifier que la demande existe
    if (!$demande) {
        throw $this->createNotFoundException('Demande non trouvée');
    }

    // Récupérer les articles associés à la demande avec la quantité
    $demandeArticles = $em->getRepository(DemandeArticle::class)->findBy(['demande' => $demande]);

    // Passer les articles et la demande à la vue
    return $this->render('boutiquier/articles.html.twig', [
        'demande' => $demande,
        'demandeArticles' => $demandeArticles,
        
    ]);
}



// Route pour changer l'état d'une demande
#[Route('/demande/{id}/etat', name: 'change_etat_demande')]
public function changeEtat(int $id, Request $request, DemandeRepository $demandeRepository): Response
{
    $demande = $demandeRepository->find($id);
    if ($demande) {
        $etat = $request->request->get('etat');
        $demande->setEtat($etat);
        $demandeRepository->save($demande);
    }
    return $this->redirectToRoute('demandes_list');
}


#[Route('/demande/{id}/valider', name: 'demande_valider', methods: ['POST'])]
public function validerDemande(Demande $demande, EntityManagerInterface $entityManager): Response
{
    $demande->setEtat('validé');
    $entityManager->persist($demande);
    $entityManager->flush();

    $this->addFlash('success', 'La demande a été validée avec succès.');
    return $this->redirectToRoute('demandes_list');
}

#[Route('/demande/{id}/annuler', name: 'demande_annuler', methods: ['POST'])]
public function refuserDemande(Demande $demande, EntityManagerInterface $entityManager): Response
{
    $demande->setEtat('annuler');
    $entityManager->persist($demande);
    $entityManager->flush();

    $this->addFlash('danger', 'La demande a été annuler.');
    return $this->redirectToRoute('demandes_list');
}


#[Route('/client/{id}/ajouter-dett', name: 'add_dett', methods: ['POST'])]
public function addDett(Request $request, Client $client, EntityManagerInterface $entityManager): RedirectResponse
{
    $montant = $request->request->get('montant');
    $montantVerser = $request->request->get('montantVerser', 0);

    if ($montant === null) {
        return new JsonResponse(['error' => 'Le montant est obligatoire'], 400);
    }

    $dette = new Dette();

    // Assurer que la date est définie
    $dette->setDate(new \DateTime());  // Assigner la date actuelle (date du jour)
    $dette->setMontant($montant)
          ->setMontantVerser($montantVerser)
          ->setClient($client);

    $entityManager->persist($dette);
    $entityManager->flush();

    // Rediriger vers la page de détails du client avec les informations mises à jour
    return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
}
    

#[Route('/client/{id}/ajouter-dett-public', name: 'add_dett_public', methods: ['POST'])]
public function addDettPublic(
    Request $request, 
    Client $client, 
    EntityManagerInterface $entityManager
): RedirectResponse {
    // Cette route contourne les restrictions de sécurité (temporairement)

    $montant = $request->request->get('montant');
    $montantVerser = $request->request->get('montantVerser', 0);

    if ($montant === null) {
        return new JsonResponse(['error' => 'Le montant est obligatoire'], 400);
    }

    $dette = new Dette();

    // Assigner la date actuelle
    $dette->setDate(new \DateTime());
    $dette->setMontant($montant)
          ->setMontantVerser($montantVerser)
          ->setClient($client);

    $entityManager->persist($dette);
    $entityManager->flush();

    // Rediriger vers la page de détails du client
    return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
}

private $entityManager;

// Injection de EntityManagerInterface via le constructeur


#[Route('/client/{id}/add-dette', name: 'add_det', methods: ['GET'])]
public function addDette(int $id): Response
{
    // Récupérer le client à l'aide de l'EntityManager
    $client = $this->entityManager->getRepository(Client::class)->find($id);

    if (!$client) {
        throw $this->createNotFoundException('Client non trouvé.');
    }

    // Récupérer les articles (ajustez cela selon votre logique)
    $articles = $this->entityManager->getRepository(Article::class)->findAll();

    return $this->render('boutiquier/add_dette.html.twig', [
        'client' => $client,
        'articles' => $articles, // Passez les articles ici
    ]);
}



#[Route('/boutiquier/ajouter-dette', name: 'ajouter_dette_boutiquier', methods: ['POST'])]
public function ajouterDette(Request $request, EntityManagerInterface $em, ClientRepository $clientRepo, ArticleRepository $articleRepo)
{
    // Récupérer les données JSON envoyées par le client
    $data = json_decode($request->getContent(), true);

    // Vérifier si les données sont valides
    if (!isset($data['products']) || !isset($data['total']) || !isset($data['clientId'])) {
        return new JsonResponse(['error' => 'Données invalides'], 400);
    }

    // Récupérer le client
    $client = $clientRepo->find($data['clientId']);
    if (!$client) {
        return new JsonResponse(['error' => 'Client non trouvé'], 404);
    }

    // Créer une nouvelle instance de Dette
    $dette = new Dette();
    $dette->setClient($client);
    $dette->setMontant($data['total']);
    $dette->setMontantVerser(0); // Initialisation à 0
    $dette->setMontantRestant($data['total']); // Initialisation à total
    $dette->setDate(new \DateTime());

    // Enregistrer la dette
    $em->persist($dette);
    $em->flush(); // Sauvegarder la dette dans la base de données

    // Associer les articles à la dette via la table DetteArticle
    foreach ($data['products'] as $productData) {
        $article = $articleRepo->find($productData['id']);
        if ($article) {
            $detteArticle = new DetteArticle();
            $detteArticle->setDette($dette);
            $detteArticle->setArticle($article);
            $detteArticle->setQuantite($productData['quantity']);

            // Enregistrer la relation Dette-Article
            $em->persist($detteArticle);
        }
    }

    // Sauvegarder les changements dans la base de données
    $em->flush();

    // Retourner une réponse indiquant que l'opération a été réussie
    return new JsonResponse(['success' => true, 'message' => 'Dette ajoutée avec succès']);
}







    

 
}
