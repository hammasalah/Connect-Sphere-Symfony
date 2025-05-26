<?php

namespace App\Entity;

use App\Repository\FeedPostsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeedPostsRepository::class)]
#[ORM\Table(name: "FeedPosts")]
class FeedPosts
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "post_id", type: "integer")]
    private ?int $postId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private ?Users $userId = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\Column(name: "timestamp", type: "string", length: 255)]
    private ?string $timeStamp = null;

    #[ORM\ManyToOne(targetEntity: Events::class)]
    #[ORM\JoinColumn(name: "event_id", referencedColumnName: "id", nullable: true)]
    private ?Events $eventId = null;

    #[ORM\Column(nullable: true)]
    private ?int $isDeleted = null;

    #[ORM\Column(length: 255)]
    private ?string $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $scorePopularite = null;

    #[ORM\Column(length: 255)]
    private ?string $imagePath = null;

    #[ORM\Column(nullable: true)]
    private ?int $groupId = null;

    public function getPostId(): ?int
    {
        return $this->postId;
    }

    /**
     * Alias pour la clé primaire 'post_id'.
     * Utilisé pour éviter les erreurs lorsque Doctrine ou Symfony cherche 'id'.
     */
    public function getId(): ?int
    {
        return $this->postId;
    }

    public function getUserId(): ?Users
    {
        return $this->userId;
    }

    public function setUserId(?Users $userId): static
    {
        $this->userId = $userId;

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

    public function getEventId(): ?Events
    {
        return $this->eventId;
    }

    public function setEventId(?Events $eventId): static
    {
        $this->eventId = $eventId;

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

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(string $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getScorePopularite(): ?int
    {
        return $this->scorePopularite;
    }

    public function setScorePopularite(?int $scorePopularite): static
    {
        $this->scorePopularite = $scorePopularite;

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(string $imagePath): static
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    public function setGroupId(?int $groupId): static
    {
        $this->groupId = $groupId;

        return $this;
    }
}
