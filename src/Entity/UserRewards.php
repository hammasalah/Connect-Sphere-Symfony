<?php

namespace App\Entity;

use App\Repository\UserRewardsRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: UserRewardsRepository::class)]
class UserRewards
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private ?Users $user = null;

    #[ORM\ManyToOne(targetEntity: Rewards::class)]
    #[ORM\JoinColumn(name: "reward_id", referencedColumnName: "id", nullable: true)]
    private ?Rewards $reward = null;

    #[ORM\Column]
    private ?int $event_id = null;

    #[ORM\Column]
    private ?int $points_earned = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $earned_at = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $raison = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getReward(): ?Rewards
    {
        return $this->reward;
    }

    public function setReward(?Rewards $reward): self
    {
        $this->reward = $reward;
        return $this;
    }

    public function getEventId(): ?int
    {
        return $this->event_id;
    }

    public function setEventId(int $event_id): self
    {
        $this->event_id = $event_id;
        return $this;
    }

    public function getPointsEarned(): ?int
    {
        return $this->points_earned;
    }

    public function setPointsEarned(int $points_earned): self
    {
        $this->points_earned = $points_earned;
        return $this;
    }

    public function getEarnedAt(): ?\DateTimeInterface
    {
        return $this->earned_at;
    }

    public function setEarnedAt(\DateTimeInterface $earned_at): self
    {
        $this->earned_at = $earned_at;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getRaison(): ?string
    {
        return $this->raison;
    }

    public function setRaison(string $raison): self
    {
        $this->raison = $raison;
        return $this;
    }
}
