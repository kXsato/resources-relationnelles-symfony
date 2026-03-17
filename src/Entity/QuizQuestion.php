<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use App\Repository\QuizQuestionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: QuizQuestionRepository::class)]
class QuizQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['resource:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(readable: false)]
    #[Ignore]
    private ?Activity $activity = null;

    #[ORM\Column(length: 500)]
    #[Groups(['resource:read'])]
    private ?string $question = null;

    #[ORM\Column(length: 255)]
    #[Groups(['resource:read'])]
    private ?string $propositionA = null;

    #[ORM\Column(length: 255)]
    #[Groups(['resource:read'])]
    private ?string $propositionB = null;

    #[ORM\Column(length: 255)]
    #[Groups(['resource:read'])]
    private ?string $propositionC = null;

    #[ORM\Column]
    #[Ignore]
    private ?int $correctAnswer = null;

    public function getId(): ?int { return $this->id; }

    public function getActivity(): ?Activity { return $this->activity; }
    public function setActivity(?Activity $activity): static
    {
        $this->activity = $activity;
        return $this;
    }

    public function getQuestion(): ?string { return $this->question; }
    public function setQuestion(string $question): static
    {
        $this->question = $question;
        return $this;
    }

    public function getPropositionA(): ?string { return $this->propositionA; }
    public function setPropositionA(string $propositionA): static
    {
        $this->propositionA = $propositionA;
        return $this;
    }

    public function getPropositionB(): ?string { return $this->propositionB; }
    public function setPropositionB(string $propositionB): static
    {
        $this->propositionB = $propositionB;
        return $this;
    }

    public function getPropositionC(): ?string { return $this->propositionC; }
    public function setPropositionC(string $propositionC): static
    {
        $this->propositionC = $propositionC;
        return $this;
    }

    public function getCorrectAnswer(): ?int { return $this->correctAnswer; }
    public function setCorrectAnswer(int $correctAnswer): static
    {
        $this->correctAnswer = $correctAnswer;
        return $this;
    }
}