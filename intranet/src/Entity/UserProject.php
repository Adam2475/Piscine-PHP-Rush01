<?php

namespace App\Entity;

use App\Repository\UserProjectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserProjectRepository::class)]
#[ORM\Table(name: 'user_project')]
class UserProject
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userProjects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'userProjects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(type: 'boolean')]
    private bool $validated = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $validatedBy = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $uploadedFilePath = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;
        return $this;
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

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?User $user): self
    {
        $this->validatedBy = $user;
        return $this;
    }


    public function getUploadedFilePath(): ?string
    {
        return $this->uploadedFilePath;
    }

    public function setUploadedFilePath(?string $path): self
    {
        $this->uploadedFilePath = $path;
        return $this;
    }
}
