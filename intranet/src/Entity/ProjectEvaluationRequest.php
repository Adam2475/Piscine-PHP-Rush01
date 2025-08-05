<?php

namespace App\Entity;

use App\Repository\ProjectEvaluationRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\UserProject;

#[ORM\Entity(repositoryClass: ProjectEvaluationRequestRepository::class)]
class ProjectEvaluationRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $requester = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $evaluator = null;

    #[ORM\ManyToOne(inversedBy: 'evaluationRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(type: 'boolean')]
    private bool $validated = false;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $evaluatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequester(): ?User
    {
        return $this->requester;
    }

    public function setRequester(?User $requester): self
    {
        $this->requester = $requester;
        return $this;
    }

    public function getEvaluator(): ?User
    {
        return $this->evaluator;
    }

    public function setEvaluator(?User $evaluator): self
    {
        $this->evaluator = $evaluator;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    /**
     * Get the UserProject based on requester and project
     * This method requires an EntityManager to fetch the relationship
     */
    public function getUserProject(\Doctrine\ORM\EntityManagerInterface $entityManager): ?UserProject
    {
        if (!$this->requester || !$this->project) {
            return null;
        }

        return $entityManager->getRepository(UserProject::class)->findOneBy([
            'user' => $this->requester,
            'project' => $this->project
        ]);
    }

    public function isValidated(): bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated): self
    {
        $this->validated = $validated;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getEvaluatedAt(): ?\DateTime
    {
        return $this->evaluatedAt;
    }

    public function setEvaluatedAt(?\DateTime $evaluatedAt): self
    {
        $this->evaluatedAt = $evaluatedAt;
        return $this;
    }
}
