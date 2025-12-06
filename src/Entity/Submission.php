<?php

namespace App\Entity;

use App\Repository\SubmissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubmissionRepository::class)]
/**
 * Représente le travail rendu par un élève pour un devoir donné.
 * C'est la preuve qu'il a bien travaillé (ou pas).
 */
class Submission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * L'élève qui a rendu ce travail.
     */
    #[ORM\ManyToOne(inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $student = null;

    /**
     * Le devoir concerné.
     */
    #[ORM\ManyToOne(inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Assignment $assignment = null;

    /**
     * Les fichiers envoyés par l'élève.
     */
    #[ORM\Column]
    private array $files = [];

    /**
     * La date et l'heure précise du rendu.
     * Utile pour savoir s'il est en retard !
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $submittedAt = null;

    public function __construct()
    {
        $this->submittedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getAssignment(): ?Assignment
    {
        return $this->assignment;
    }

    public function setAssignment(?Assignment $assignment): static
    {
        $this->assignment = $assignment;

        return $this;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(array $files): static
    {
        $this->files = $files;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function isLate(): bool
    {
        $dueDate = $this->getAssignment()->getDueDate();
        return $dueDate && $this->getSubmittedAt() > $dueDate;
    }
}
