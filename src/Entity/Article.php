<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use ApiPlatform\Metadata\ApiProperty;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article extends Resource
{
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['resource:read'])]
    private ?string $content = null;

    #[ORM\OneToMany(targetEntity: Favorite::class, mappedBy: 'article', orphanRemoval: true)]
    #[ApiProperty(readable: false)]
    #[Ignore]
    private Collection $favorites;

    public function __construct()
    {
        parent::__construct();
        $this->favorites = new ArrayCollection();
    }

    public function getContent(): ?string { return $this->content; }

    public function setContent(?string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getFavorites(): Collection { return $this->favorites; }

    public function addFavorite(Favorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setArticle($this);
        }
        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            if ($favorite->getArticle() === $this) {
                $favorite->setArticle(null);
            }
        }
        return $this;
    }
}