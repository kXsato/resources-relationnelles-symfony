<?php

namespace App\Entity;

use App\Repository\UserRessourceProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRessourceProgressRepository::class)]
class UserRessourceProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column]
    private ?int $readPrecentage = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $completeAt = null;

    #[ORM\ManyToOne(inversedBy: 'userRessourceProgress')]
    private ?User $UserRessources = null;

    #[ORM\ManyToOne(inversedBy: 'userRessourceProgresses')]
    private ?Resource $resource = null;

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

    public function getReadPrecentage(): ?int
    {
        return $this->readPrecentage;
    }

    public function setReadPrecentage(int $readPrecentage): static
    {
        $this->readPrecentage = $readPrecentage;

        if ($readPrecentage >= 100) {
            $this->status = 'completed';
            $this->completeAt = $this->completeAt ?? new \DateTime();
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
}
