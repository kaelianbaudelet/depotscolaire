<?php

namespace App\Entity;

use App\Repository\AssignmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssignmentRepository::class)]
/**
 * Représente un devoir donné à faire à une classe.
 * Contient le titre, la description, la date limite et les fichiers joints.
 * C'est le cauchemar des élèves et le plaisir des profs (ou l'inverse ?).
 */
class Assignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Le titre du devoir. Court et efficace.
     */
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    /**
     * Les consignes détaillées.
     * C'est ici qu'on explique tout ce qu'il faut faire pour avoir 20/20.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * La date et l'heure limite pour rendre le devoir.
     * Après cette heure, c'est trop tard ! (Enfin, ça dépend de la gentillesse du prof).
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    /**
     * Liste des noms de fichiers joints par le professeur (PDF, images, etc.).
     */
    #[ORM\Column]
    private array $attachments = [];
    
    /**
     * Si true, les élèves peuvent rendre leur devoir même après la date limite.
     * Si false, le formulaire de rendu disparaît à la seconde où la date est passée.
     */
    #[ORM\Column(options: ['default' => false])]
    private ?bool $allowLateSubmissions = false;

    /**
     * La classe à qui ce devoir est destiné.
     */
    #[ORM\ManyToOne(inversedBy: 'assignments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Classroom $classroom = null;

    /**
     * Les travaux rendus par les élèves.
     */
    #[ORM\OneToMany(mappedBy: 'assignment', targetEntity: Submission::class, orphanRemoval: true)]
    private Collection $submissions;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->submissions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function setAttachments(array $attachments): static
    {
        $this->attachments = $attachments;

        return $this;
    }

    public function getClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    public function setClassroom(?Classroom $classroom): static
    {
        $this->classroom = $classroom;

        return $this;
    }

    /**
     * @return Collection<int, Submission>
     */
    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    public function addSubmission(Submission $submission): static
    {
        if (!$this->submissions->contains($submission)) {
            $this->submissions->add($submission);
            $submission->setAssignment($this);
        }

        return $this;
    }

    public function removeSubmission(Submission $submission): static
    {
        if ($this->submissions->removeElement($submission)) {
            // set the owning side to null (unless already changed)
            if ($submission->getAssignment() === $this) {
                $submission->setAssignment(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isAllowLateSubmissions(): ?bool
    {
        return $this->allowLateSubmissions;
    }

    public function setAllowLateSubmissions(bool $allowLateSubmissions): static
    {
        $this->allowLateSubmissions = $allowLateSubmissions;

        return $this;
    }
}
