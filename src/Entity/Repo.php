<?php

namespace App\Entity;

use App\Repository\RepoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

#[ORM\Entity(repositoryClass: RepoRepository::class)]
#[UniqueEntity('url', message: 'This url is already in use.')]
class Repo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $url;

    #[ORM\Column(type: 'guid')]
    private string $uuid;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $totalCommits;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $evaluatedCommits;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $clonedAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $latestKnownCommit;

    #[ORM\Column(type: 'boolean')]
    private $cloned;

    #[ORM\Column(type: 'boolean')]
    private $golangMetricsCalculated;

    #[ORM\Column(type: 'boolean')]
    private $rustMetricsCalculated;

    #[ORM\Column(type: 'boolean')]
    private $customMetricsCalculated;

    public function __construct()
    {
        $this->cloned = false;
        $this->golangMetricsCalculated = false;
        $this->rustMetricsCalculated = false;
        $this->customMetricsCalculated = false;
        $this->uuid = Uuid::v4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getTotalCommits(): ?int
    {
        return $this->totalCommits;
    }

    public function setTotalCommits(?int $totalCommits): self
    {
        $this->totalCommits = $totalCommits;

        return $this;
    }

    public function getClonedAt(): ?\DateTimeImmutable
    {
        return $this->clonedAt;
    }

    public function setClonedAt(?\DateTimeImmutable $clonedAt): self
    {
        $this->clonedAt = $clonedAt;

        return $this;
    }

    public function getLatestKnownCommit(): ?string
    {
        return $this->latestKnownCommit;
    }

    public function setLatestKnownCommit(?string $latestKnownCommit): self
    {
        $this->latestKnownCommit = $latestKnownCommit;

        return $this;
    }

    public function isCloned(): ?bool
    {
        return $this->cloned;
    }

    public function setCloned(bool $cloned): self
    {
        $this->cloned = $cloned;

        return $this;
    }

    public function isGolangMetricsCalculated(): ?bool
    {
        return $this->golangMetricsCalculated;
    }

    public function setGolangMetricsCalculated(bool $golangMetricsCalculated): self
    {
        $this->golangMetricsCalculated = $golangMetricsCalculated;

        return $this;
    }

    public function isRustMetricsCalculated(): ?bool
    {
        return $this->rustMetricsCalculated;
    }

    public function setRustMetricsCalculated(bool $rustMetricsCalculated): self
    {
        $this->rustMetricsCalculated = $rustMetricsCalculated;

        return $this;
    }

    public function isCustomMetricsCalculated(): ?bool
    {
        return $this->customMetricsCalculated;
    }

    public function setCustomMetricsCalculated(bool $customMetricsCalculated): self
    {
        $this->customMetricsCalculated = $customMetricsCalculated;

        return $this;
    }

    public function getEvaluatedCommits(): int
    {
        return $this->evaluatedCommits;
    }

    public function setEvaluatedCommits(int $evaluatedCommits): void
    {
        $this->evaluatedCommits = $evaluatedCommits;
    }

}
