<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: "client", uniqueConstraints: [
    new ORM\UniqueConstraint(name: "unique_surname", columns: ["surname"]),
    new ORM\UniqueConstraint(name: "unique_telephone", columns: ["telephone"])
])]

class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $surname = null;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\ManyToOne(inversedBy: 'clients')]
    private ?User $userAccount = null;

    /**
     * @var Collection<int, Dette>
     */
    #[ORM\OneToMany(targetEntity: Dette::class, mappedBy: 'client')]
    private Collection $dettes;

    // Le champ userAccountId ajouté
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $userAccountId = null;

    public function __construct()
    {
        $this->dettes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getUserAccount(): ?User
    {
        return $this->userAccount;
    }

    public function setUserAccount(?User $userAccount): static
    {
        $this->userAccount = $userAccount;

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
            $dette->setClient($this);
        }

        return $this;
    }

    public function getTotalMontantRestant(): float
    {
        $totalMontantRestant = 0;
        
        // Parcours des dettes du client
        foreach ($this->dettes as $dette) {
            // Calcul du montant restant pour chaque dette
            $montantRestant = $dette->getMontant() - $dette->getMontantVerser();
            $totalMontantRestant += $montantRestant;  // Additionne le montant restant pour chaque dette
        }
        
        return $totalMontantRestant;
    }

    // Méthode pour vérifier si le client a un compte utilisateur
    public function hasUserAccount(): bool
    {
        return $this->userAccountId !== null;
    }
}
