<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[UniqueEntity('url', message: 'This url is already in use.')]
class Project
{
    public const INITIALIZED = 'initialized';
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

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $status;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: StatisticFile::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $statistics;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: StatisticFile::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $statisticFiles;
    public function __construct()
    {
        $this->status = self::INITIALIZED;
        $this->uuid = Uuid::v4();
        $this->statistics = new ArrayCollection();
        $this->statisticFiles = new ArrayCollection();
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

    public function getEvaluatedCommits(): int
    {
        return $this->evaluatedCommits;
    }

    public function setEvaluatedCommits(int $evaluatedCommits): void
    {
        $this->evaluatedCommits = $evaluatedCommits;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return Collection<int, StatisticFile>
     */
    public function getStatisticFiles(): Collection
    {
        return $this->statisticFiles;
    }

    public function getStatisticFilesByFunctionName(string $functionName): ?StatisticFile
    {
        $result = $this->statisticFiles->filter(function (StatisticFile $statFile) use ($functionName) {
            return $statFile->getFunctionName() === $functionName;
        });
        if($result->count() !== 0) {
            return $result->first();
        }
        return null;
    }

    public function addStatisticFile(StatisticFile $statisticFile): self
    {
        if (!$this->statisticFiles->contains($statisticFile)) {
            $this->statisticFiles[] = $statisticFile;
            $statisticFile->setProject($this);
        }

        return $this;
    }

    public function removeStatisticFile(StatisticFile $statisticFile): self
    {
        if ($this->statisticFiles->removeElement($statisticFile)) {
            // set the owning side to null (unless already changed)
            if ($statisticFile->getProject() === $this) {
                $statisticFile->setProject(null);
            }
        }

        return $this;
    }

    public function hasStatisticFile(string $functionName): bool
    {
        return $this->getStatisticFiles()->exists(function (StatisticFile $statFile) use ($functionName) {
            return $statFile->getFunctionName() === $functionName;
        });
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status): void
    {
        $this->status = $status;
    }

}
