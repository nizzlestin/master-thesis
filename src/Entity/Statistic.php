<?php

namespace App\Entity;

use App\Repository\StatisticRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatisticRepository::class)]
#[ORM\Index(fields: ["commit"], name: "commit_idx")]
#[ORM\Index(fields: ["file"], name: "file_idx")]
#[ORM\Index(fields: ["project"], name: "project_idx")]
class Statistic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $project;

    #[ORM\Column(type: 'string', length: 255)]
    private $commit;

    #[ORM\Column(type: 'string', length: 255)]
    private $language;

    #[ORM\Column(type: 'string', length: 255)]
    private $file;

    #[ORM\Column(type: 'string', length: 255)]
    private $fileDirname;

    #[ORM\Column(type: 'string', length: 255)]
    private $fileBasename;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $byte;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $blank;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $comment;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $code;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $complexity;

    public function getId(): int
    {
        return $this->id;
    }

    public function getProject(): string
    {
        return $this->project;
    }

    public function setProject(string $project): void
    {
        $this->project = $project;
    }

    public function getCommit()
    {
        return $this->commit;
    }

    public function setCommit($commit): void
    {
        $this->commit = $commit;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language): void
    {
        $this->language = $language;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file): void
    {
        $this->file = $file;
    }

    public function getFileDirname()
    {
        return $this->fileDirname;
    }

    public function setFileDirname($fileDirname): void
    {
        $this->fileDirname = $fileDirname;
    }

    public function getFileBasename()
    {
        return $this->fileBasename;
    }

    public function setFileBasename($fileBasename): void
    {
        $this->fileBasename = $fileBasename;
    }

    public function getByte()
    {
        return $this->byte;
    }

    public function setByte($byte): void
    {
        $this->byte = $byte;
    }

    public function getBlank()
    {
        return $this->blank;
    }

    public function setBlank($blank): void
    {
        $this->blank = $blank;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment): void
    {
        $this->comment = $comment;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code): void
    {
        $this->code = $code;
    }

    public function getComplexity()
    {
        return $this->complexity;
    }

    public function setComplexity($complexity): void
    {
        $this->complexity = $complexity;
    }

}
