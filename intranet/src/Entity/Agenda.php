<?php

namespace App\Entity;

use App\Repository\AgendaRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Event;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: AgendaRepository::class)]
class Agenda
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // #[ORM\OneToMany(mappedBy:'agenda', targetEntity: User::class)]
    // private Collection $users;

    #[ORM\OneToMany(mappedBy: 'agenda', targetEntity: Event::class, cascade: ['persist', 'remove'])]
    private Collection $events;
    
    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->users = new ArrayCollection();
    }
    
    public function getEvents(): Collection
    {
        return $this->events;
    }
    
    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->setAgenda($this);
        }
    
        return $this;
    }
    
    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            if ($event->getAgenda() === $this) {
                $event->setAgenda(null);
            }
        }
    
        return $this;
    }
    public function getId(): ?int
    {
        return $this->id;
    }
}
