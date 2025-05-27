<?php

namespace App\Entity;

use App\Repository\UsersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
#[ORM\Table(name: 'users')]
class Users
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = 1;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    
    

    #[ORM\Column(nullable: true)]
    private ?int $points = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $age = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $argent = null;

    #[ORM\OneToMany(targetEntity: Conversion::class, mappedBy: 'userId')]
    private Collection $conversions;

    #[ORM\OneToMany(targetEntity: Events::class, mappedBy: 'organizerId')]
    private Collection $events;

    #[ORM\OneToMany(targetEntity: Jobs::class, mappedBy: 'userId', orphanRemoval: true)]
    private Collection $jobs;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserProfile $profile = null;

    //partie souha 
       #[ORM\OneToMany(mappedBy: 'sender', targetEntity: Message::class, cascade: ['persist', 'remove'])]
    private Collection $sentMessages;
    
    #[ORM\OneToMany(mappedBy: 'receiver', targetEntity: Message::class, cascade: ['persist', 'remove'])]
    private Collection $receivedMessages;

    public function __construct()
    {
        $this->conversions = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->jobs = new ArrayCollection();
         $this->sentMessages = new ArrayCollection();
        $this->receivedMessages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): self
    {
        $this->points = $points;
        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): self
    {
        $this->age = $age;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getArgent(): ?string
    {
        return $this->argent;
    }

    public function setArgent(?string $argent): self
    {
        $this->argent = $argent;
        return $this;
    }

    public function getConversions(): Collection
    {
        return $this->conversions;
    }

    public function addConversion(Conversion $conversion): self
    {
        if (!$this->conversions->contains($conversion)) {
            $this->conversions->add($conversion);
            $conversion->setUserId($this);
        }
        return $this;
    }

    public function removeConversion(Conversion $conversion): self
    {
        if ($this->conversions->removeElement($conversion)) {
            if ($conversion->getUserId() === $this) {
                $conversion->setUserId(null);
            }
        }
        return $this;
    }

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Events $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setOrganizerId($this);
        }
        return $this;
    }

    public function removeEvent(Events $event): self
    {
        if ($this->events->removeElement($event)) {
            if ($event->getOrganizerId() === $this) {
                $event->setOrganizerId(null);
            }
        }
        return $this;
    }

    public function getJobs(): Collection
    {
        return $this->jobs;
    }

    public function addJob(Jobs $job): self
    {
        if (!$this->jobs->contains($job)) {
            $this->jobs->add($job);
            $job->setUserId($this);
        }
        return $this;
    }

    public function removeJob(Jobs $job): self
    {
        if ($this->jobs->removeElement($job)) {
            if ($job->getUserId() === $this) {
                $job->setUserId(null);
            }
        }
        return $this;
    }

    public function getProfile(): ?UserProfile
    {
        return $this->profile;
    }

    public function setProfile(?UserProfile $profile): self
    {
        $this->profile = $profile;

        if ($profile !== null && $profile->getUser() !== $this) {
            $profile->setUser($this);
        }

        return $this;
    }
     //Partie SOUHAAAAAAAAAA
     
    public function getSentMessages(): Collection
{
    return $this->sentMessages;
}

public function addSentMessage(Message $message): self
{
    if (!$this->sentMessages->contains($message)) {
        $this->sentMessages[] = $message;
        $message->setSender($this);
    }

    return $this;
}

public function removeSentMessage(Message $message): self
{
    if ($this->sentMessages->removeElement($message)) {
        if ($message->getSender() === $this) {
            $message->setSender(null);
        }
    }

    return $this;
}

public function getReceivedMessages(): Collection
{
    return $this->receivedMessages;
}

public function addReceivedMessage(Message $message): self
{
    if (!$this->receivedMessages->contains($message)) {
        $this->receivedMessages[] = $message;
        $message->setReceiver($this);
    }

    return $this;
}

public function removeReceivedMessage(Message $message): self
{
    if ($this->receivedMessages->removeElement($message)) {
        if ($message->getReceiver() === $this) {
            $message->setReceiver(null);
        }
    }

    return $this;
}
   
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

      public function getProfilePicture(): ?string
    {
        return $this->profile ? $this->profile->getProfilePicture() : null;
    }
}
