<?php
// src/Entity/Participation.php
namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types; // For ORM type definitions

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'participation')] // Explicitly map to your existing 'participation' table
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')] // Assumes 'id' is auto-incrementing in your MySQL table
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    // Maps to the 'event_id' column in your 'participation' table
    #[ORM\ManyToOne(targetEntity: Events::class)]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', nullable: false, onDelete: "CASCADE")]
    private ?Events $event = null;

    // Maps to the 'participant_id' column in your 'participation' table
    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'participant_id', referencedColumnName: 'id', nullable: false, onDelete: "CASCADE")]
    private ?Users $participant = null;

    

    public function __construct()
    {
        
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Events
    {
        return $this->event;
    }

    public function setEvent(?Events $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getParticipant(): ?Users
    {
        return $this->participant;
    }

    public function setParticipant(?Users $participant): static
    {
        $this->participant = $participant;
        return $this;
}

}