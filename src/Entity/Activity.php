<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
class Activity extends Resource
{
    public const GAME_TYPES = [
        'Quiz' => 'quiz',
    ];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gameType = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxParticipants = null;

    public function __construct()
    {
        parent::__construct();
        $this->setStatus('published');
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

    public function getMaxParticipants(): ?int { return $this->maxParticipants; }

    public function setMaxParticipants(?int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;
        return $this;
    }
}