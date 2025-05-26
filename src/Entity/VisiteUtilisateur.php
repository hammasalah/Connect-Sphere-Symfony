<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'visite_utilisateur')]
class VisiteUtilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'date')]
    private $dernier_visite;

    #[ORM\Column(type: 'integer')]
    private $serie;



    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'visites')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private $user;

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDernierVisite(): \DateTimeInterface
    {
        return $this->dernier_visite;
    }

    public function setDernierVisite(\DateTimeInterface $dernier_visite): self
    {
        $this->dernier_visite = $dernier_visite;
        return $this;
    }

    public function getSerie(): int
    {
        return $this->serie;
    }

    public function setSerie(int $serie): self
    {
        $this->serie = $serie;
        return $this;
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

}