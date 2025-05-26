<?php

namespace App\Entity;

use App\Repository\CommentsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentsRepository::class)]
#[ORM\Table(name: "Comments")]
class Comments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "post_id", referencedColumnName: "post_id", nullable: false)]
    private ?FeedPosts $postId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $user_id = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    private ?string $timeStamp = null;

    #[ORM\Column(nullable: true)]
    private ?int $isDeleted = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPostId(): ?FeedPosts
    {
        return $this->postId;
    }

    public function setPostId(?FeedPosts $postId): static
    {
        $this->postId = $postId;

        return $this;
    }

    public function getUserId(): ?Users
    {
        return $this->user_id;
    }

    public function setUserId(?Users $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getTimeStamp(): ?string
    {
        return $this->timeStamp;
    }

    public function setTimeStamp(string $timeStamp): static
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }

    public function getIsDeleted(): ?int
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(?int $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }
}
