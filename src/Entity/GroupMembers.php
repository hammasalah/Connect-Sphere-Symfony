<?php

namespace App\Entity;

use App\Repository\GroupMembersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupMembersRepository::class)]
#[ORM\Table(name: 'group_members')]
class GroupMembers
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]

    //modiif
    #[ORM\JoinColumn(name: "group_it_id", referencedColumnName: "id", nullable: false)]
    private ?UserGroups $group_it = null;

    #[ORM\ManyToOne]
    //modiif 
    #[ORM\JoinColumn(name: "user_id_id", referencedColumnName: "id", nullable: false)]
    private ?Users $user_id = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;
    
    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_PENDING;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupIt(): ?UserGroups
    {
        return $this->group_it;
    }

    public function setGroupIt(?UserGroups $group_it): static
    {
        $this->group_it = $group_it;

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

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

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
    
    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(?string $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}