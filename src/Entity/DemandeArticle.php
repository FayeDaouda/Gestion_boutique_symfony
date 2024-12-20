<?php

namespace App\Entity;

use App\Repository\DemandeArticleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DemandeArticleRepository::class)]
#[ORM\Table(name: 'demande_article')] // Spécifie le nom de la table
class DemandeArticle
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'demandeArticles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Demande $demande = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'demandeArticles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Article $article = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La quantité est obligatoire.')]
    #[Assert\Positive(message: 'La quantité doit être un nombre positif.')]
    private ?int $quantite = null;

    // Getters et Setters
    public function getDemande(): ?Demande
    {
        return $this->demande;
    }

    public function setDemande(?Demande $demande): static
    {
        $this->demande = $demande;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }
}
