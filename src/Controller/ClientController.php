<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use App\Entity\Dette;
use App\Entity\Demande;
use App\Entity\Paiement;
use App\Entity\DemandeArticle;
use App\Repository\DemandeRepository;
use App\Repository\DetteRepository; // 
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Article; // Import de l'entité Article
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security; // Pour Symfony 5.3 ou ultérieur
use Knp\Component\Pager\PaginatorInterface; 

class ClientController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/client/dettes', name: 'client_dettes')]
    public function listDettes(EntityManagerInterface $entityManager): Response
    {
        $user = $this->security->getUser();
        $client = $entityManager->getRepository(Client::class)->findOneBy(['userAccount' => $user->getId()]);

        if (!$client) {
            throw $this->createNotFoundException('Client introuvable pour cet utilisateur.');
        }

        $dettes = $entityManager->getRepository(Dette::class)->findBy(['client' => $client]);

        $totalMontant = array_sum(array_map(fn($dette) => $dette->getMontant(), $dettes));
        $totalMontantVerser = array_sum(array_map(fn($dette) => $dette->getMontantVerser(), $dettes));
        $totalMontantRestant = $totalMontant - $totalMontantVerser;

        return $this->render('client/dettes.html.twig', [
            'client' => $client,
            'dettes' => $dettes,
            'totalMontant' => $totalMontant,
            'totalMontantVerser' => $totalMontantVerser,
            'totalMontantRestant' => $totalMontantRestant,
        ]);
    }
    
    #[Route('/client/demandes-dette', name: 'client_demandes_dette')]
    public function afficherDemandesDette(EntityManagerInterface $entityManager): Response
    {
        $user = $this->security->getUser();
        $client = $entityManager->getRepository(Client::class)->findOneBy(['userAccount' => $user->getId()]);

        if (!$client) {
            throw $this->createNotFoundException('Client introuvable pour cet utilisateur.');
        }

        $demandes = $entityManager->getRepository(Demande::class)->findBy(['client' => $client]);

        $totalDemandes = count($demandes);
        $totalMontant = array_sum(array_map(fn($demande) => $demande->getMontant(), $demandes));

        return $this->render('client/demandes_dette.html.twig', [
            'client' => $client,
            'demandes' => $demandes,
            'totalDemandes' => $totalDemandes,
            'totalMontant' => $totalMontant,
        ]);
    }

   // Route to display the form (GET method)
#[Route('/client/faire-demande', name: 'faire_demande', methods: ['GET'])]
public function faireDemande(EntityManagerInterface $entityManager): Response
{
    $user = $this->security->getUser();

    // Check if the user is logged in
    if (!$user) {
        return $this->redirectToRoute('login'); // Redirect if the user is not logged in
    }

    // Get the client associated with the user
    $client = $entityManager->getRepository(Client::class)->findOneBy(['userAccount' => $user->getId()]);

    if (!$client) {
        throw $this->createNotFoundException('Client not found for this user.');
    }

    // Get available articles (with quantity in stock > 0)
    $articles = $entityManager->getRepository(Article::class)->createQueryBuilder('a')
        ->where('a.quantiteStock > 0') // Ensure column name is correct
        ->getQuery()
        ->getResult();

    return $this->render('client/faireDemande.html.twig', [
        'client' => $client,
        'articles' => $articles,
    ]);
}

#[Route('/client/faire-demande', name: 'faire_demande_post', methods: ['POST'])]
public function soumettreDemande(Request $request, EntityManagerInterface $entityManager): Response
{
    // Récupérer l'utilisateur actuellement connecté
    $utilisateur = $this->security->getUser();

    // Vérifier si l'utilisateur est connecté
    if (!$utilisateur) {
        return $this->redirectToRoute('login');
    }

    // Récupérer le client associé à l'utilisateur
    $client = $entityManager->getRepository(Client::class)->findOneBy(['userAccount' => $utilisateur->getId()]);

    if (!$client) {
        throw $this->createNotFoundException('Client non trouvé pour cet utilisateur.');
    }

    // Récupérer les articles sélectionnés depuis la requête (données JSON)
    $data = json_decode($request->getContent(), true);
    $articlesSelectionnes = $data['products'] ?? [];
    $montantTotal = $data['total'] ?? 0;

    // Vérifier si des articles ont été sélectionnés
    if (empty($articlesSelectionnes)) {
        return $this->json(['success' => false, 'message' => 'Aucun article sélectionné.']);
    }

    // Créer une nouvelle demande
    $demande = new Demande();
    $demande->setClient($client);
    $demande->setEtat('En Cours'); // Statut initial de la demande
    $demande->setDateDemande(new \DateTime()); // Date actuelle
    $demande->setMontant($montantTotal);

    // Parcourir les articles sélectionnés et vérifier le stock
    foreach ($articlesSelectionnes as $articleData) {
        $article = $entityManager->getRepository(Article::class)->find($articleData['id']);

        if (!$article) {
            return $this->json(['success' => false, 'message' => "Article introuvable (ID: {$articleData['id']})"]);
        }

        if ($article->getQuantiteStock() < $articleData['quantity']) {
            return $this->json(['success' => false, 'message' => "Stock insuffisant pour l'article : {$article->getNom()}"]);
        }
    }

    // Sauvegarder la demande
    $entityManager->persist($demande);
    $entityManager->flush(); // À ce stade, la demande a un ID généré

    // Insérer les données dans la table d'association demande_article
    foreach ($articlesSelectionnes as $articleData) {
        $article = $entityManager->getRepository(Article::class)->find($articleData['id']);

        // Ajouter la relation dans la table demande_article
        $conn = $entityManager->getConnection();
        $conn->insert('demande_article', [
            'demande_id' => $demande->getId(),
            'article_id' => $article->getId(),
            'quantite' => $articleData['quantity'],
        ]);

        // Mettre à jour le stock de l'article
        $article->setQuantiteStock($article->getQuantiteStock() - $articleData['quantity']);
        $entityManager->persist($article);
    }

    // Sauvegarder les modifications
    $entityManager->flush();

    // Retourner une réponse de succès
    return $this->json(['success' => true, 'message' => 'Demande soumise avec succès.']);
}


#[Route('/client/dette/{id}/relancer', name: 'client_relancer_dette', methods: ['POST'])]
public function relancerDette(int $id, DemandeRepository $demandeRepository, EntityManagerInterface $entityManager): Response
{
    // Récupérer la demande par ID
    $demande = $demandeRepository->find($id);

    if (!$demande) {
        throw $this->createNotFoundException('La demande est introuvable.');
    }

    // Vérifier si la demande est annulée
    if ($demande->getEtat() !== 'annuler') {
        $this->addFlash('error', 'Seules les demandes annulées peuvent être relancées.');
        return $this->redirectToRoute('client_demandes_dette'); // Rediriger vers la page des demandes
    }

    // Modifier l'état de la demande pour indiquer qu'elle est relancée
    $demande->setEtat('Relancée'); // Nouveau statut
    

    // Persister les changements
    $entityManager->persist($demande);
    $entityManager->flush();

    // Ajouter un message de succès
    $this->addFlash('success', 'La demande a été relancée avec succès.');

    // Redirection après l'action
    return $this->redirectToRoute('client_demandes_dette'); // Vers la liste des demandes
}


#[Route('/dette/payer', name: 'dette_payer', methods: ['POST'])]
 public function payerDette(Request $request, DetteRepository $detteRepository, EntityManagerInterface $em): Response
{
    $detteId = $request->request->get('dette_id');
    $montantPaye = $request->request->get('montantPaye');

    $dette = $detteRepository->find($detteId);
    if (!$dette) {
        $this->addFlash('error', 'Dette introuvable.');
        return $this->redirectToRoute('dette_list');
    }

    if ($montantPaye > ($dette->getMontant() - $dette->getMontantVerser())) {
        $this->addFlash('error', 'Montant payé dépasse le montant restant.');
        return $this->redirectToRoute('dette_list');
    }

    // Mettre à jour la dette
    $dette->setMontantVerser($dette->getMontantVerser() + $montantPaye);
    $em->persist($dette);
    $em->flush();

    // Créer un nouvel enregistrement dans la table paiements
    $paiement = new Paiement();
    $paiement->setDette($dette);
    $paiement->setMontant($montantPaye);
    $paiement->setDate(new \DateTime()); // La date actuelle du paiement

    $em->persist($paiement);
    $em->flush();

    $this->addFlash('success', 'Paiement enregistré avec succès.');
    
    // Rediriger vers l'URL spécifiée
    return $this->redirect('http://127.0.0.1:8000/client/dettes');
}

#[Route('/client/dette/{id}/details', name: 'dette_details')]
public function afficherDetailsDette(int $id, EntityManagerInterface $entityManager): Response
{
    $user = $this->security->getUser();
    $client = $entityManager->getRepository(Client::class)->findOneBy(['userAccount' => $user->getId()]);

    if (!$client) {
        throw $this->createNotFoundException('Client introuvable pour cet utilisateur.');
    }

    $dette = $entityManager->getRepository(Dette::class)->findOneBy(['id' => $id, 'client' => $client]);

    if (!$dette) {
        throw $this->createNotFoundException('Dette introuvable ou non associée au client connecté.');
    }

    $paiements = $dette->getPaiements();
    $articles = $dette->getArticles();

    return $this->render('client/dette_details.html.twig', [
        'dette' => $dette,
        'paiements' => $paiements,
        'articles' => $articles,
    ]);
}




}
