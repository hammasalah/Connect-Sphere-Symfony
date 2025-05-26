<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'conversion')]
class Conversion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'conversions')]
    #[ORM\JoinColumn(name: 'user_id_id', referencedColumnName: 'id', nullable: true)]
    private ?Users $userId = null;

    #[ORM\Column(type: 'integer')]
    private int $points_convertis;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $montant;

    #[ORM\Column(type: 'string', length: 255)]
    private string $devise;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date;

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?Users
    {
        return $this->userId;
    }

    public function setUserId(?Users $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getPointsConvertis(): int
    {
        return $this->points_convertis;
    }

    public function setPointsConvertis(int $points_convertis): self
    {
        $this->points_convertis = $points_convertis;
        return $this;
    }

    public function getMontant(): string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): self
    {
        $this->montant = $montant;
        return $this;
    }

    public function getDevise(): string
    {
        return $this->devise;
    }

    public function setDevise(string $devise): self
    {
        $this->devise = $devise;
        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }
}