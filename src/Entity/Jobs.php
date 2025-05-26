<?php

namespace App\Entity;

use App\Repository\JobsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobsRepository::class)]
class Jobs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $jobTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $eventTitle = null;

    #[ORM\Column(length: 255)]
    private ?string $jobLocation = null;

    #[ORM\Column(length: 255)]
    private ?string $employmentType = null;

    #[ORM\Column(length: 255 , nullable: true)]
    private ?string $applicationDeadLine = null;

    #[ORM\Column]
    private ?int $minSalary = null;

    #[ORM\Column]
    private ?int $maxSalary = null;

    #[ORM\Column(length: 255)]
    private ?string $currency = null;

    #[ORM\Column(length: 255)]
    private ?string $jobDescreption = null;

    #[ORM\Column(length: 255)]
    private ?string $recruiterName = null;

    #[ORM\Column(length: 255)]
    private ?string $recruiterEmail = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $createdAt ;
    
    #[ORM\ManyToOne(inversedBy: 'jobs')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Users $userId = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function getEventTitle(): ?string
    {
        return $this->eventTitle;
    }

    public function setEventTitle(?string $eventTitle): static
    {
        $this->eventTitle = $eventTitle;

        return $this;
    }

    public function getJobLocation(): ?string
    {
        return $this->jobLocation;
    }

    public function setJobLocation(string $jobLocation): static
    {
        $this->jobLocation = $jobLocation;

        return $this;
    }

    public function getEmploymentType(): ?string
    {
        return $this->employmentType;
    }

    public function setEmploymentType(string $employmentType): static
    {
        $this->employmentType = $employmentType;

        return $this;
    }

    public function getApplicationDeadLine(): ?string
    {
        return $this->applicationDeadLine;
    }

    public function setApplicationDeadLine(string $applicationDeadLine): static
    {
        $this->applicationDeadLine = $applicationDeadLine;

        return $this;
    }

    public function getMinSalary(): ?int
    {
        return $this->minSalary;
    }

    public function setMinSalary(int $minSalary): static
    {
        $this->minSalary = $minSalary;

        return $this;
    }

    public function getMaxSalary(): ?int
    {
        return $this->maxSalary;
    }

    public function setMaxSalary(int $maxSalary): static
    {
        $this->maxSalary = $maxSalary;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getJobDescreption(): ?string
    {
        return $this->jobDescreption;
    }

    public function setJobDescreption(string $jobDescreption): static
    {
        $this->jobDescreption = $jobDescreption;

        return $this;
    }

    public function getRecruiterName(): ?string
    {
        return $this->recruiterName;
    }

    public function setRecruiterName(string $recruiterName): static
    {
        $this->recruiterName = $recruiterName;

        return $this;
    }

    public function getRecruiterEmail(): ?string
    {
        return $this->recruiterEmail;
    }

    public function setRecruiterEmail(string $recruiterEmail): static
    {
        $this->recruiterEmail = $recruiterEmail;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

  

    public function getUserId(): ?Users
    {
        return $this->userId;
    }

    public function setUserId(?Users $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

   
}