<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $login = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var Collection<int, Client>
     */
    #[ORM\OneToMany(targetEntity: Client::class, mappedBy: 'userAccount')]
    private Collection $clients;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }
    /**
 * Vérifie si l'utilisateur possède un rôle spécifique.
 */
public function hasRole(string $role): bool
{
    return in_array($role, $this->getRoles());
}


public function getRoles(): array
{
    return array_unique($this->roles);
}

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return Collection<int, Client>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): static
    {
        if (!$this->clients->contains($client)) {
            $this->clients->add($client);
            $client->setUserAccount($this);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        if ($this->clients->removeElement($client)) {
            if ($client->getUserAccount() === $this) {
                $client->setUserAccount(null);
            }
        }

        return $this;
    }

    /**
     * Méthode de l'interface UserInterface
     * Retourne l'email de l'utilisateur comme identifiant
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * Méthode de l'interface PasswordAuthenticatedUserInterface
     * Cette méthode permet de vider les informations sensibles, comme les mots de passe temporaires
     */
    public function eraseCredentials(): void
    {
        // Si vous avez des informations sensibles à effacer, vous pouvez le faire ici
        // Par exemple, si vous stockez un mot de passe temporaire ou des données sensibles, effacez-les ici.
        // Dans ce cas, on laisse la méthode vide si aucune donnée sensible n'est présente.
    }

            #[ORM\Column(type: 'boolean')]
        private $isActive = true;

        public function getIsActive(): bool
        {
            return $this->isActive;
        }

        public function setIsActive(bool $isActive): self
        {
            $this->isActive = $isActive;
            return $this;
        }

}
