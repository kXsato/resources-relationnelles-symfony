<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use App\State\UserRegistrationProcessor;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['user:read']]),
        new Post(
            denormalizationContext: ['groups' => ['user:write']],
            normalizationContext: ['groups' => ['user:read']],
            processor: UserRegistrationProcessor::class,
            security: 'is_granted("PUBLIC_ACCESS")',
        ),
    ],
)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse e-mail est déjà utilisée.')]
#[UniqueEntity(fields: ['userName'], message: 'Il y a déjà un compte avec ce nom d\'utilisateur')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank(message: 'L\'email est requis.', groups: ['Default', 'user:write'])]
    #[Assert\Email(message: 'L\'adresse email n\'est pas valide.', groups: ['Default', 'user:write'])]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank(message: 'Le nom d\'utilisateur est requis.', groups: ['Default', 'user:write'])]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le nom d\'utilisateur doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom d\'utilisateur ne peut pas dépasser {{ limit }} caractères.',
        groups: ['Default', 'user:write']
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_\-\.]+$/',
        message: 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, underscores, tirets et points.',
        groups: ['Default', 'user:write']
    )]
    private ?string $userName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['user:read', 'user:write'])]
    private ?\DateTime $birthDate = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTime $lastLogin = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?\DateTime $registrationDate = null;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['user:read'])]
    private bool $isAccountActivated = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $reactivationRequestedAt = null;

    #[ORM\OneToMany(targetEntity: Resource::class, mappedBy: 'author')]
    #[ApiProperty(readable: false)]
    #[Ignore]
    private Collection $resources;

    #[ORM\OneToMany(targetEntity: Favorite::class, mappedBy: 'user', orphanRemoval: true)]
    #[ApiProperty(readable: false)]
    #[Ignore]
    private Collection $favorites;

    #[Groups(['user:write'])]
    #[Assert\NotBlank(message: 'Veuillez saisir un mot de passe.', groups: ['Default', 'user:write'])]
    #[Assert\Length(
        min: 6,
        max: 15,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le mot de passe ne peut pas dépasser {{ limit }} caractères.',
        groups: ['Default', 'user:write']
    )]
    #[Assert\Regex(
        pattern: '/[A-Z]/',
        message: 'Le mot de passe doit contenir au moins une majuscule.',
        groups: ['Default', 'user:write']
    )]
    #[Assert\Regex(
        pattern: '/\d/',
        message: 'Le mot de passe doit contenir au moins un chiffre.',
        groups: ['Default', 'user:write']
    )]
    private ?string $plainPassword = null;

    #[ORM\OneToMany(targetEntity: UserRessourceProgress::class, mappedBy: 'UserRessources', cascade: ['remove'], orphanRemoval: true)]
    #[ApiProperty(readable: false)]
    #[Ignore]
    private Collection $userRessourceProgress;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user', orphanRemoval: true)]
    #[ApiProperty(readable: false)]
    #[Ignore]
    private Collection $comments;

    public function __construct()
    {
        $this->resources = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->userRessourceProgress = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string { return $this->password; }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function __toString(): string
    {
        return $this->userName ?? $this->email ?? '';
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
        return $data;
    }

    public function getUserName(): ?string { return $this->userName; }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;
        return $this;
    }

    public function getBirthDate(): ?\DateTime { return $this->birthDate; }

    public function setBirthDate(\DateTime $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getLastLogin(): ?\DateTime { return $this->lastLogin; }

    public function setLastLogin(\DateTime $lastLogin): static
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    #[ORM\PrePersist]
    public function initDatesOnCreate(): void
    {
        $this->registrationDate = new \DateTime();
    }

    public function getRegistrationDate(): ?\DateTime { return $this->registrationDate; }

    public function setRegistrationDate(\DateTime $registrationDate): static
    {
        $this->registrationDate = $registrationDate;
        return $this;
    }

    #[Ignore]
    public function getResources(): Collection { return $this->resources; }

    public function addResource(Resource $resource): static
    {
        if (!$this->resources->contains($resource)) {
            $this->resources->add($resource);
            $resource->setAuthor($this);
        }
        return $this;
    }

    public function removeResource(Resource $resource): static
    {
        if ($this->resources->removeElement($resource)) {
            if ($resource->getAuthor() === $this) {
                $resource->setAuthor(null);
            }
        }
        return $this;
    }

    #[Ignore]
    public function getFavorites(): Collection { return $this->favorites; }

    public function addFavorite(Favorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setUser($this);
        }
        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            if ($favorite->getUser() === $this) {
                $favorite->setUser(null);
            }
        }
        return $this;
    }

    #[Groups(['user:read'])]
    public function isAccountActivated(): bool { return $this->isAccountActivated; }

    public function getAccountStatus(): string
    {
        return $this->isAccountActivated ? 'Oui' : 'Non';
    }

    public function setIsAccountActivated(bool $isAccountActivated): static
    {
        $this->isAccountActivated = $isAccountActivated;
        return $this;
    }

    public function getReactivationRequestedAt(): ?\DateTime { return $this->reactivationRequestedAt; }

    public function setReactivationRequestedAt(?\DateTime $reactivationRequestedAt): static
    {
        $this->reactivationRequestedAt = $reactivationRequestedAt;
        return $this;
    }

    public function getRole(): string
    {
        $specialRoles = array_filter($this->roles, fn($r) => $r !== 'ROLE_USER');
        return !empty($specialRoles) ? reset($specialRoles) : 'ROLE_USER';
    }

    public function setRole(string $role): static
    {
        $this->roles = $role !== 'ROLE_USER' ? [$role] : [];
        return $this;
    }

    public function getPlainPassword(): ?string { return $this->plainPassword; }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    #[Ignore]
    public function getUserRessourceProgress(): Collection { return $this->userRessourceProgress; }

    public function addUserRessourceProgress(UserRessourceProgress $userRessourceProgress): static
    {
        if (!$this->userRessourceProgress->contains($userRessourceProgress)) {
            $this->userRessourceProgress->add($userRessourceProgress);
            $userRessourceProgress->setUserRessources($this);
        }
        return $this;
    }

    public function removeUserRessourceProgress(UserRessourceProgress $userRessourceProgress): static
    {
        if ($this->userRessourceProgress->removeElement($userRessourceProgress)) {
            if ($userRessourceProgress->getUserRessources() === $this) {
                $userRessourceProgress->setUserRessources(null);
            }
        }
        return $this;
    }

    #[Ignore]
    public function getComments(): Collection { return $this->comments; }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setUser($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }
        return $this;
    }
}