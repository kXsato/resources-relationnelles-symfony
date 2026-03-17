<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use ApiPlatform\Metadata\ApiProperty;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
class Activity extends Resource
{
    public const GAME_TYPES = [
        'Quiz' => 'quiz',
    ];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['resource:read'])]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['resource:list', 'resource:read'])]
    private ?string $gameType = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['resource:list', 'resource:read'])]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['resource:list', 'resource:read'])]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\OneToMany(targetEntity: QuizQuestion::class, mappedBy: 'activity', orphanRemoval: true)]
    #[Groups(['resource:read'])]
    private Collection $questions;

    public function __construct()
    {
        parent::__construct();
        $this->setStatus('published');
        $this->questions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle() ?? '';
    }

    public function getContent(): ?string { return $this->content; }

    public function setContent(?string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getGameType(): ?string { return $this->gameType; }

    public function setGameType(?string $gameType): static
    {
        $this->gameType = $gameType;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable { return $this->startDate; }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable { return $this->endDate; }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getQuestions(): Collection { return $this->questions; }

    public function addQuestion(QuizQuestion $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setActivity($this);
        }
        return $this;
    }

    public function removeQuestion(QuizQuestion $question): static
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getActivity() === $this) {
                $question->setActivity(null);
            }
        }
        return $this;
    }
}