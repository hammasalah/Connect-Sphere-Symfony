<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Users;

#[ORM\Entity]
#[ORM\Table(name: 'roulette')]
class Roulette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'roulettes')]
    #[ORM\JoinColumn(name: "user_id", nullable: true)]
    private ?Users $user = null;

   // #[ORM\Column(name: "user_id", type: 'integer', insertable: false, updatable: false)]
    //private ?int $user_id = null;



    #[ORM\Column(type: 'datetime', name: "created_at")]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $result = null;

    /**
     * Constructeur pour initialiser createdAt avec la date actuelle.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime(); // Initialisation par défaut avec la date actuelle.
    }
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

    //public function getUserId(): ?int
   // {
     //   return $this->user_id;
    //}

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }


    // Ajout : Ajouté les getters et setters pour le champ "result".
    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(string $result): self
    {
        $this->result = $result;
        return $this;
    }
}