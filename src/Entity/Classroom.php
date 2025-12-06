<?php

namespace App\Entity;

use App\Repository\ClassroomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClassroomRepository::class)]
/**
 * Représente une classe (un groupe d'élèves avec un professeur).
 * C'est l'espace principal d'échange.
 */
class Classroom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Le nom de la classe (ex: "BTS SIO 1ère année").
     */
    #[ORM\Column(length: 255)]
    private ?string $name = null;



    /**
     * Le professeur responsable de la classe.
     * C'est lui le chef !
     */
    #[ORM\ManyToOne(inversedBy: 'teachingClassrooms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $teacher = null;

    /**
     * La liste des élèves inscrits dans cette classe.
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'joinedClassrooms')]
    private Collection $students;

    #[ORM\OneToMany(mappedBy: 'classroom', targetEntity: Assignment::class, orphanRemoval: true)]
    private Collection $assignments;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->students = new ArrayCollection();
        $this->assignments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }



    public function getTeacher(): ?User
    {
        return $this->teacher;
    }

    public function setTeacher(?User $teacher): static
    {
        $this->teacher = $teacher;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(User $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->addJoinedClassroom($this);
        }

        return $this;
    }

    public function removeStudent(User $student): static
    {
        if ($this->students->removeElement($student)) {
            $student->removeJoinedClassroom($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Assignment>
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(Assignment $assignment): static
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments->add($assignment);
            $assignment->setClassroom($this);
        }

        return $this;
    }

    public function removeAssignment(Assignment $assignment): static
    {
        if ($this->assignments->removeElement($assignment)) {
            // set the owning side to null (unless already changed)
            if ($assignment->getClassroom() === $this) {
                $assignment->setClassroom(null);
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
}
