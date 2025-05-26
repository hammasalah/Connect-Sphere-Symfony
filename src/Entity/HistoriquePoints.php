<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'historique_points')]
class HistoriquePoints
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'historiquePoints')]
    #[ORM\JoinColumn(name: 'user_id_id', referencedColumnName: 'id', nullable: true)] // Correction : Ajout de nullable: true pour correspondre à la table
    private ?Users $user = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)] // Correction : Ajout de nullable: true car la table permet NULL
    private ?string $type = null;

    #[ORM\Column(type: 'integer', nullable: true)] // Correction : Ajout de nullable: true car la table permet NULL
    private ?int $points = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)] // Correction : Ajout de nullable: true car la table permet NULL
    private ?string $raison = null;

    #[ORM\Column(type: 'datetime', nullable: true)] // Correction : Ajout de nullable: true car la table permet NULL
    private ?\DateTimeInterface $date = null;

    // Getters et Setters
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }

    public function getRaison(): string
    {
        return $this->raison;
    }

    public function setRaison(string $raison): self
    {
        $this->raison = $raison;
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