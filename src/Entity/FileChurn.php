<?php

namespace App\Entity;

use App\Repository\FileChurnRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Index(fields: ["project", "file"], name: "project_file_idx")]
#[ORM\Entity(repositoryClass: FileChurnRepository::class)]
class FileChurn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $churn;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $wordFix;

    #[ORM\Column(type: 'float', nullable: true)]
    private $avgGrowth;


    #[ORM\Column(type: 'string', length: 255)]
    private $file;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $project;

    public function __construct(int $churn, string $file, Project $project)
    {
        $this->churn = $churn;
        $this->project = $project;
        $this->file = $file;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChurn(): ?int
    {
        return $this->churn;
    }

    public function setChurn(int $churn): self
    {
        $this->churn = $churn;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;

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

    public function getWordFix()
    {
        return $this->wordFix;
    }

    public function setWordFix($wordFix): void
    {
        $this->wordFix = $wordFix;
    }

    public function getAvgGrowth()
    {
        return $this->avgGrowth;
    }

    public function setAvgGrowth($avgGrowth): void
    {
        $this->avgGrowth = $avgGrowth;
    }
}
