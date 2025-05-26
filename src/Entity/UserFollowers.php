<?php

namespace App\Entity;
use App\Repository\UserFollowersRepository;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserFollowersRepository::class)]
#[ORM\Table(name: "UserFollowers")]
class UserFollowers
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: "follower_id", referencedColumnName: "id", nullable: false)]
    private ?Users $follower = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: "followed_id", referencedColumnName: "id", nullable: false)]
    private ?Users $followed = null;

    #[ORM\Column(length: 255)]
    private ?string $created_at = null;
    
    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_PENDING;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFollower(): ?Users
    {
        return $this->follower;
    }

    public function setFollower(?Users $follower): static
    {
        $this->follower = $follower;

        return $this;
    }

    public function getFollowed(): ?Users
    {
        return $this->followed;
    }

    public function setFollowed(?Users $followed): static
    {
        $this->followed = $followed;

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(string $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
    
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
    
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
    
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }
    
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}