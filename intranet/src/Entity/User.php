<?php

namespace App\Entity;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'This email is already registered.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $first_name = null;

    #[ORM\Column(length: 60)]
    private ?string $last_name = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $created = null;

    #[ORM\Column(enumType: UserRole::class)]
    private ?UserRole $role = null;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $confirmationToken = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = false;

    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'users')]
    private Collection $events;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $experience = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $evalPoints;

    #[ORM\OneToMany(targetEntity: EvalSlot::class, mappedBy: 'userId', orphanRemoval: true)]
    private Collection $evalSlots;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserProject::class, orphanRemoval: true)]
    private Collection $userProjects;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $notifications = [];

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $unreadNotificationsCount = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $level = 1;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->experience = 0;
        $this->evalPoints = 5;
        $this->evalSlots = new ArrayCollection();
        $this->userProjects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;
        return $this;
    }

    public function getEvalPoints(): int
    {
        return $this->evalPoints;
    }

    public function setEvalPoints(int $evalPoints): static
    {
        $this->evalPoints = $evalPoints;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): static
    {
        $this->created = $created;
        return $this;
    }

    public function getRole(): ?UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): static
    {
        $this->confirmationToken = $confirmationToken;
        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        if (!$this->role) {
            return [];
        }

        return ['ROLE_' . strtoupper($this->role->value)];
    }

    public function eraseCredentials(): void
    {
        // Clear temporary sensitive data if any
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->addUser($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            $event->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, UserProject>
     */
    public function getUserProjects(): Collection
    {
        return $this->userProjects;
    }

    public function addUserProject(UserProject $userProject): static
    {
        if (!$this->userProjects->contains($userProject)) {
            $this->userProjects->add($userProject);
            $userProject->setUser($this);
        }

        return $this;
    }

    public function removeUserProject(UserProject $userProject): static
    {
        if ($this->userProjects->removeElement($userProject)) {
            if ($userProject->getUser() === $this) {
                $userProject->setUser(null);
            }
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getExperience(): int
    {
        return $this->experience;
    }

    public function setExperience(int $experience): static
    {
        $this->experience = $experience;
        return $this;
    }

    public function addExperience(int $xp): static
    {
        $this->experience += $xp;
        return $this;
    }

    /**
     * @return Collection<int, EvalSlot>
     */
    public function getEvalSlots(): Collection
    {
        return $this->evalSlots;
    }

    public function addEvalSlot(EvalSlot $evalSlot): static
    {
        if (!$this->evalSlots->contains($evalSlot)) {
            $this->evalSlots->add($evalSlot);
            $evalSlot->setUserId($this);
        }

        return $this;
    }

    public function removeEvalSlot(EvalSlot $evalSlot): static
    {
        if ($this->evalSlots->removeElement($evalSlot)) {
            if ($evalSlot->getUserId() === $this) {
                $evalSlot->setUserId(null);
            }
        }

        return $this;
    }

    public function getNotifications(): ?array
    {
        return $this->notifications;
    }

    public function addNotification(string $message, ?string $link = null): static
    {
        $this->notifications[] = [
            'message' => $message,
            'link' => $link,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ];
        $this->unreadNotificationsCount++;

        return $this;
    }

    public function removeNotification(int $index): static
    {
        if (isset($this->notifications[$index]))
        {
            unset($this->notifications[$index]);
            $this->notifications = array_values($this->notifications);
            $this->unreadNotificationsCount = max(0, $this->unreadNotificationsCount - 1);
        }
        return $this;
    }

    public function getUnreadNotificationsCount(): int
    {
        return $this->unreadNotificationsCount;
    }

    public function setUnreadNotificationsCount(int $count): static
    {
        $this->unreadNotificationsCount = $count;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;
        return $this;
    }
}
