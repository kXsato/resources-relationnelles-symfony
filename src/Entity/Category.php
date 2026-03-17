<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use ApiPlatform\Metadata\ApiProperty;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['resource:list', 'resource:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['resource:list', 'resource:read'])]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Resource::class, mappedBy: 'categories')]
    #[ApiProperty(readable: false)]
    #[Ignore]
    private Collection $resources;

    public function __construct()
    {
        $this->resources = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function __toString(): string { return $this->name ?? ''; }

    #[Ignore]
    public function getResources(): Collection { return $this->resources; }

    public function getResourceCount(): int { return $this->resources->count(); }
}