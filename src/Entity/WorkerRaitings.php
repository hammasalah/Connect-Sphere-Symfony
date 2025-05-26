<?php

namespace App\Entity;

use App\Repository\WorkerRaitingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkerRaitingsRepository::class)]
class WorkerRaitings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $user_id = null;

    #[ORM\Column]
    private ?int $rater_id = null;

    #[ORM\Column]
    private ?int $job_id = null;

    #[ORM\Column]
    private ?int $raiting = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getRaterId(): ?int
    {
        return $this->rater_id;
    }

    public function setRaterId(int $rater_id): static
    {
        $this->rater_id = $rater_id;

        return $this;
    }

    public function getJobId(): ?int
    {
        return $this->job_id;
    }

    public function setJobId(int $job_id): static
    {
        $this->job_id = $job_id;

        return $this;
    }

    public function getRaiting(): ?int
    {
        return $this->raiting;
    }

    public function setRaiting(int $raiting): static
    {
        $this->raiting = $raiting;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
