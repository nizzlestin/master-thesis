<?php

namespace App\Entity;

use App\Repository\EvaluationStatsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvaluationStatsRepository::class)]
class EvaluationStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $url;

    #[ORM\Column(type: 'integer')]
    private $elapsedTime;

    #[ORM\Column(type: 'integer')]
    private $numberOfFiles;

    #[ORM\Column(type: 'integer')]
    private $numberOfCommits;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $project;

    #[ORM\Column(type: 'string', length: 255)]
    private $task;

    #[ORM\Column(type: 'integer')]
    private $memory;

    #[ORM\Column(type: 'bigint')]
    private $sloc;

    #[ORM\Column(type: 'integer')]
    private $overallCommits;


    public function __construct()
    {
        $this->project = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getElapsedTime(): ?int
    {
        return $this->elapsedTime;
    }

    public function setElapsedTime(int $elapsedTime): self
    {
        $this->elapsedTime = $elapsedTime;

        return $this;
    }

    public function getNumberOfFiles(): ?int
    {
        return $this->numberOfFiles;
    }

    public function setNumberOfFiles(int $numberOfFiles): self
    {
        $this->numberOfFiles = $numberOfFiles;

        return $this;
    }

    public function getNumberOfCommits(): ?int
    {
        return $this->numberOfCommits;
    }

    public function setNumberOfCommits(int $numberOfCommits): self
    {
        $this->numberOfCommits = $numberOfCommits;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getTask(): ?string
    {
        return $this->task;
    }

    public function setTask(string $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function getMemory(): ?int
    {
        return $this->memory;
    }

    public function setMemory(int $memory): self
    {
        $this->memory = $memory;

        return $this;
    }

    public function getSloc(): ?int
    {
        return $this->sloc;
    }

    public function setSloc(int $sloc): self
    {
        $this->sloc = $sloc;

        return $this;
    }

    public function getOverallCommits(): ?int
    {
        return $this->overallCommits;
    }

    public function setOverallCommits(int $overallCommits): self
    {
        $this->overallCommits = $overallCommits;

        return $this;
    }
}
