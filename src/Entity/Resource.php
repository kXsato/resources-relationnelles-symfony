<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ResourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: 20)]
#[ORM\DiscriminatorMap([
    'article' => Article::class,
    'activity' => Activity::class,
])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/resources',
            normalizationContext: ['groups' => ['resource:list']],
        ),
    ],
    security: "is_granted('PUBLIC_ACCESS')",
)]
abstract class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['resource:list', 'resource:read', 'progress:read', 'favorite:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['resource:list', 'resource:read', 'progress:read', 'favorite:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['resource:read', 'progress:read', 'favorite:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['resource:list', 'resource:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['resource:list', 'resource:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['resource:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 20)]
    #[Groups(['resource:read'])]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Ignore]
    private ?string $rejectionReason = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'resources')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[ApiProperty(readable: false)]
    #[Ignore]
    private ?User $author = null;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'resources')]
    #[ORM\JoinTable(name: 'resource_category')]
    #[Groups(['resource:list', 'resource:read'])]
    private Collection $categories;

    #[ORM\OneToMany(targetEntity: UserRessourceProgress::class, mappedBy: 'resource', orphanRemoval: true)]
    #[ApiProperty(readable: false)]
    #[Ignore]
    private Collection $userRessourceProgresses;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'resource', orphanRemoval: true)]
    #[ApiProperty(readable: false)]
    #[Ignore]
    private Collection $comments;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->userRessourceProgresses = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): ?string { return $this->slug; }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string { return $this->description; }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function initTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $this->createdAt ?? $now;
        $this->updatedAt = $this->updatedAt ?? $now;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getStatus(): ?string { return $this->status; }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getRejectionReason(): ?string { return $this->rejectionReason; }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function getAuthor(): ?User { return $this->author; }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    #[Groups(['resource:list', 'resource:read'])]
    public function getDisplayAuthor(): string
    {
        if ($this->author === null || !$this->author->isAccountActivated()) {
            return 'Anonyme';
        }
        return $this->author->getUserName();
    }

    #[Groups(['resource:list', 'resource:read'])]
    public function getResourceType(): string
    {
        $parts = explode('\\', static::class);
        return strtolower(end($parts));
    }

    public function getCategories(): Collection { return $this->categories; }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }
        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);
        return $this;
    }

    public function getUserRessourceProgresses(): Collection { return $this->userRessourceProgresses; }

    public function addUserRessourceProgress(UserRessourceProgress $userRessourceProgress): static
    {
        if (!$this->userRessourceProgresses->contains($userRessourceProgress)) {
            $this->userRessourceProgresses->add($userRessourceProgress);
            $userRessourceProgress->setResource($this);
        }
        return $this;
    }

    public function removeUserRessourceProgress(UserRessourceProgress $userRessourceProgress): static
    {
        if ($this->userRessourceProgresses->removeElement($userRessourceProgress)) {
            if ($userRessourceProgress->getResource() === $this) {
                $userRessourceProgress->setResource(null);
            }
        }
        return $this;
    }

    public function getComments(): Collection { return $this->comments; }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setResource($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getResource() === $this) {
                $comment->setResource(null);
            }
        }
        return $this;
    }
}