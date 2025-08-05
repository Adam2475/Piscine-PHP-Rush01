<?php
namespace App\Entity;

use App\Entity\UserProject;
use App\Entity\ProjectEvaluationRequest;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'integer')]
    private int $xp;

    #[ORM\Column(type: 'integer')]
    private int $estimatedTimeInHours;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: UserProject::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userProjects;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectEvaluationRequest::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $evaluationRequests;

    public function __construct()
    {
        $this->userProjects = new ArrayCollection();
        $this->evaluationRequests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getXp(): int
    {
        return $this->xp;
    }

    public function setXp(int $xp): self
    {
        $this->xp = $xp;
        return $this;
    }

    public function getEstimatedTimeInHours(): int
    {
        return $this->estimatedTimeInHours;
    }

    public function setEstimatedTimeInHours(int $hours): self
    {
        $this->estimatedTimeInHours = $hours;
        return $this;
    }

    /**
     * @return Collection<int, UserProject>
     */
    public function getUserProjects(): Collection
    {
        return $this->userProjects;
    }

    public function addUserProject(UserProject $userProject): self
    {
        if (!$this->userProjects->contains($userProject)) {
            $this->userProjects[] = $userProject;
            $userProject->setProject($this);
        }

        return $this;
    }

    public function removeUserProject(UserProject $userProject): self
    {
        if ($this->userProjects->removeElement($userProject)) {
            if ($userProject->getProject() === $this) {
                $userProject->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProjectEvaluationRequest>
     */
    public function getEvaluationRequests(): Collection
    {
        return $this->evaluationRequests;
    }

    public function addEvaluationRequest(ProjectEvaluationRequest $request): self
    {
        if (!$this->evaluationRequests->contains($request)) {
            $this->evaluationRequests[] = $request;
            $request->setProject($this);
        }

        return $this;
    }

    public function removeEvaluationRequest(ProjectEvaluationRequest $request): self
    {
        if ($this->evaluationRequests->removeElement($request)) {
            if ($request->getProject() === $this) {
                $request->setProject(null);
            }
        }

        return $this;
    }
}
