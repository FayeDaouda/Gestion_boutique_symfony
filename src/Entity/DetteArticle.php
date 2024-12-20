<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DetteArticleRepository::class)]
#[ORM\Table(name: 'dette_article')]
class DetteArticle
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Dette::class, inversedBy: 'detteArticles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Dette $dette = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'detteArticles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Article $article = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'La quantité est obligatoire.')]
    #[Assert\Positive(message: 'La quantité doit être un nombre positif.')]
    private ?int $quantite = null;

    // Getters et Setters
    public function getDette(): ?Dette
    {
        return $this->dette;
    }

    public function setDette(?Dette $dette): static
    {
        $this->dette = $dette;
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
