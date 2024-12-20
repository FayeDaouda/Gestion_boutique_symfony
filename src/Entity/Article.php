<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(name: "quantite_stock")]
    private ?int $quantiteStock = null;

    #[ORM\Column]
    private ?float $prix = null;

    /**
     * @var Collection<int, Dette>
     */
    #[ORM\ManyToMany(targetEntity: Dette::class, mappedBy: 'articles')]
    private Collection $dettes;

    /**
     * @var Collection<int, DemandeArticle>
     */
    #[ORM\OneToMany(targetEntity: DemandeArticle::class, mappedBy: 'article', cascade: ['persist', 'remove'])]
    private Collection $demandeArticles;

    public function __construct()
    {
        $this->dettes = new ArrayCollection();
        $this->demandeArticles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getQuantiteStock(): ?int
    {
        return $this->quantiteStock;
    }

    public function setQuantiteStock(int $quantiteStock): static
    {
        $this->quantiteStock = $quantiteStock;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    /**
     * @return Collection<int, Dette>
     */
    public function getDettes(): Collection
    {
        return $this->dettes;
    }

    public function addDette(Dette $dette): static
    {
        if (!$this->dettes->contains($dette)) {
            $this->dettes->add($dette);
            $dette->addArticle($this);
        }

        return $this;
    }

    public function removeDette(Dette $dette): static
    {
        if ($this->dettes->removeElement($dette)) {
            $dette->removeArticle($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, DemandeArticle>
     */
    public function getDemandeArticles(): Collection
    {
        return $this->demandeArticles;
    }

    public function addDemandeArticle(DemandeArticle $demandeArticle): static
    {
        if (!$this->demandeArticles->contains($demandeArticle)) {
            $this->demandeArticles->add($demandeArticle);
            $demandeArticle->setArticle($this);
        }

        return $this;
    }

    public function removeDemandeArticle(DemandeArticle $demandeArticle): static
    {
        if ($this->demandeArticles->removeElement($demandeArticle)) {
            if ($demandeArticle->getArticle() === $this) {
                $demandeArticle->setArticle(null);
            }
        }

        return $this;
    }
}
