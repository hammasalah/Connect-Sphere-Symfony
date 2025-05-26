<?php

namespace App\Entity;

use App\Repository\LikesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LikesRepository::class)]
#[ORM\Table(name: "Likes")]

class Likes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "post_id", referencedColumnName: "post_id", nullable: false)]
    private ?FeedPosts $postId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "id", referencedColumnName: "id", nullable: false)]
    private ?Users $user_id = null;

    #[ORM\Column(length: 255)]
    private ?string $timeStamp = null;

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

    public function getTimeStamp(): ?string
    {
        return $this->timeStamp;
    }

    public function setTimeStamp(string $timeStamp): static
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }
}
