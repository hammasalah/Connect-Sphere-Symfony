<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: "Messages")]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "message_id", type: "integer")]
    private ?int $messageId = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "sentMessages")]
    #[ORM\JoinColumn(name: "sender_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Users $sender = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "receivedMessages")]
    #[ORM\JoinColumn(name: "recipient_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Users $receiver = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: "timestamp", options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(type: Types::STRING, length: 20, name: "type")]
    private ?string $type = "MESSAGE";

    #[ORM\Column(type: Types::BOOLEAN, name: "read_status", options: ["default" => false])]
    private bool $readStatus = false;

    public function __construct()
    {
        $this->timestamp = new \DateTime();
        $this->type = "MESSAGE";
        $this->readStatus = false;
    }

    public function getMessageId(): ?int
    {
        return $this->messageId;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getSender(): ?Users
    {
        return $this->sender;
    }

    public function setSender(?Users $sender): self
    {
        if ($this->sender !== $sender) {
            $this->sender?->getSentMessages()->removeElement($this);
            $this->sender = $sender;
            $sender?->addSentMessage($this);
        }
        return $this;
    }

    public function getReceiver(): ?Users
    {
        return $this->receiver;
    }

    public function setReceiver(?Users $receiver): self
    {
        if ($this->receiver !== $receiver) {
            $oldReceiver = $this->receiver;
            $this->receiver = $receiver;
            if ($oldReceiver !== null) {
                $oldReceiver->removeReceivedMessage($this);
            }
            if ($receiver !== null) {
                $receiver->addReceivedMessage($this);
            }
        }
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

    public function getReadStatus(): bool
    {
        return $this->readStatus;
    }

    public function setReadStatus(bool $readStatus): self
    {
        $this->readStatus = $readStatus;
        return $this;
    }

    public function isSentBy(Users $user): bool
    {
        return $this->sender && $this->sender->getId() === $user->getId();
    }
}
