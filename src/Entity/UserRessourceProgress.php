<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRessourceProgressRepository;
use App\State\UserRessourceProgressProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRessourceProgressRepository::class)]
#[ApiResource(
    shortName: 'Progress',
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['progress:read']],
        ),
        new Get(
            security: "is_granted('ROLE_USER') and object.getUserRessources() == user",
            normalizationContext: ['groups' => ['progress:read']],
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['progress:read']],
            denormalizationContext: ['groups' => ['progress:write']],
            processor: UserRessourceProgressProcessor::class,
        ),
        new Patch(
            security: "is_granted('ROLE_USER') and object.getUserRessources() == user",
            normalizationContext: ['groups' => ['progress:read']],
            denormalizationContext: ['groups' => ['progress:patch']],
        ),
    ],
)]
class UserRessourceProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['progress:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['progress:read'])]
    private ?string $status = null;

    #[ORM\Column]
    #[Groups(['progress:read', 'progress:write', 'progress:patch'])]
    private ?int $readPercentage = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['progress:read'])]
    private ?\DateTime $completeAt = null;

    #[ORM\ManyToOne(inversedBy: 'userRessourceProgress')]
    private ?User $UserRessources = null;

    #[ORM\ManyToOne(inversedBy: 'userRessourceProgresses')]
    #[Groups(['progress:read'])]
    private ?Resource $resource = null;

    /** Transient — utilisé uniquement en écriture (POST), résolu par le processor */
    #[ApiProperty]
    #[Groups(['progress:write'])]
    private ?int $resourceId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getReadPercentage(): ?int
    {
        return $this->readPercentage;
    }

    public function setReadPercentage(int $readPercentage): static
    {
        $this->readPercentage = $readPercentage;

        if ($readPercentage >= 100) {
            $this->status = 'completed';
            $this->completeAt = $this->completeAt ?? new \DateTime();
        } else {
            $this->status = 'in_progress';
        }

        return $this;
    }

    public function getCompleteAt(): ?\DateTime
    {
        return $this->completeAt;
    }

    public function setCompleteAt(?\DateTime $completeAt): static
    {
        $this->completeAt = $completeAt;

        return $this;
    }

    public function getUserRessources(): ?User
    {
        return $this->UserRessources;
    }

    public function setUserRessources(?User $UserRessources): static
    {
        $this->UserRessources = $UserRessources;

        return $this;
    }

    public function getResource(): ?Resource
    {
        return $this->resource;
    }

    public function setResource(?Resource $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    public function getResourceId(): ?int
    {
        return $this->resourceId;
    }

    public function setResourceId(?int $resourceId): static
    {
        $this->resourceId = $resourceId;

        return $this;
    }
}
