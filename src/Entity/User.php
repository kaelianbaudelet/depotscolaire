<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
/**
 * L'utilisateur du système.
 * Peut être un élève, un professeur, ou un administrateur.
 * C'est la personne centrale de l'application.
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * L'adresse email de l'utilisateur.
     * Sert aussi d'identifiant pour se connecter.
     */
    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Veuillez renseigner votre adresse email.')]
    #[Assert\Email(message: 'Cette valeur {{ value }} n\'est pas une adresse email valide.')]
    private ?string $email = null;

    /**
     * Les rôles de l'utilisateur (ex: ROLE_USER, ROLE_TEACHER, ROLE_ADMIN).
     * Définit ce qu'il a le droit de faire ou de voir.
     *
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * Historique des mots de passe.
     * Pour éviter qu'il réutilise le même mot de passe trop souvent.
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Password::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $passwordHistory;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'current_password_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Password $currentPassword = null;

    /**
     * Est-ce que l'utilisateur a vérifié son email ?
     */
    #[ORM\Column]
    private bool $isVerified = false;

    /**
     * Est-ce que le compte est suspendu (banni) ?
     * Si oui, il ne peut plus rien faire. Puni !
     */
    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private bool $isSuspended = false;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Veuillez renseigner votre prenom.')]
    #[Assert\Length(max: 100, maxMessage: 'Le prenom ne peut pas depasser {{ limit }} caracteres.')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Veuillez renseigner votre nom.')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas depasser {{ limit }} caracteres.')]
    private ?string $lastName = null;

    /**
     * Coordonnées postales complètes.
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Veuillez renseigner votre adresse postale.')]
    #[Assert\Length(max: 255, maxMessage: 'L\'adresse ne peut pas depasser {{ limit }} caracteres.')]
    private ?string $address = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Veuillez renseigner votre ville.')]
    #[Assert\Length(max: 150, maxMessage: 'La ville ne peut pas depasser {{ limit }} caracteres.')]
    private ?string $city = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Veuillez renseigner votre code postal.')]
    #[Assert\Regex(pattern: '/^\\d{5}$/', message: 'Le code postal doit contenir 5 chiffres.')]
    private ?string $postalCode = null;

    #[ORM\Column(type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Logs>
     */
    #[ORM\OneToMany(targetEntity: Logs::class, mappedBy: 'User')]
    private Collection $logs;

    /**
     * Les classes enseignées par cet utilisateur (s'il est professeur).
     */
    #[ORM\OneToMany(mappedBy: 'teacher', targetEntity: Classroom::class, orphanRemoval: true)]
    private Collection $teachingClassrooms;

    /**
     * Les classes rejointes par cet utilisateur (s'il est étudiant).
     */
    #[ORM\ManyToMany(targetEntity: Classroom::class, inversedBy: 'students')]
    private Collection $joinedClassrooms;

    /**
     * Les devoirs rendus par cet élève.
     */
    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Submission::class, orphanRemoval: true)]
    private Collection $submissions;

    public function __construct()
    {
        $this->passwordHistory = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->logs = new ArrayCollection();
        $this->teachingClassrooms = new ArrayCollection();
        $this->joinedClassrooms = new ArrayCollection();
        $this->submissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection<int, Password>
     */
    public function getPasswordHistory(): Collection
    {
        return $this->passwordHistory;
    }

    public function addPassword(Password $password): static
    {
        if (!$this->passwordHistory->contains($password)) {
            $this->passwordHistory->add($password);
            $password->setUser($this);
        }

        return $this;
    }

    public function removePassword(Password $password): static
    {
        if ($this->passwordHistory->removeElement($password) && $this->currentPassword === $password) {
            $first = $this->passwordHistory->first();
            if ($first instanceof Password) {
                $this->currentPassword = $first;
            } else {
                throw new \LogicException('Un utilisateur doit conserver au moins un mot de passe.');
            }
        }

        return $this;
    }

    public function getCurrentPassword(): ?Password
    {
        return $this->currentPassword;
    }

    public function setCurrentPassword(Password $password): static
    {
        $this->addPassword($password);
        $this->currentPassword = $password;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->currentPassword?->getHash();
    }

    public function setPassword(string $password): static
    {
        $passwordEntity = (new Password())
            ->setHash($password);

        $this->addPassword($passwordEntity);
        $this->currentPassword = $passwordEntity;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isSuspended(): bool
    {
        return $this->isSuspended;
    }

    public function setIsSuspended(bool $isSuspended): static
    {
        $this->isSuspended = $isSuspended;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, Logs>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(Logs $log): static
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setUser($this);
        }

        return $this;
    }

    public function removeLog(Logs $log): static
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getUser() === $this) {
                $log->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Classroom>
     */
    public function getTeachingClassrooms(): Collection
    {
        return $this->teachingClassrooms;
    }

    public function addTeachingClassroom(Classroom $teachingClassroom): static
    {
        if (!$this->teachingClassrooms->contains($teachingClassroom)) {
            $this->teachingClassrooms->add($teachingClassroom);
            $teachingClassroom->setTeacher($this);
        }

        return $this;
    }

    public function removeTeachingClassroom(Classroom $teachingClassroom): static
    {
        if ($this->teachingClassrooms->removeElement($teachingClassroom)) {
            // set the owning side to null (unless already changed)
            if ($teachingClassroom->getTeacher() === $this) {
                $teachingClassroom->setTeacher(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Classroom>
     */
    public function getJoinedClassrooms(): Collection
    {
        return $this->joinedClassrooms;
    }

    public function addJoinedClassroom(Classroom $joinedClassroom): static
    {
        if (!$this->joinedClassrooms->contains($joinedClassroom)) {
            $this->joinedClassrooms->add($joinedClassroom);
        }

        return $this;
    }

    public function removeJoinedClassroom(Classroom $joinedClassroom): static
    {
        $this->joinedClassrooms->removeElement($joinedClassroom);

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
            $submission->setStudent($this);
        }

        return $this;
    }

    public function removeSubmission(Submission $submission): static
    {
        if ($this->submissions->removeElement($submission)) {
            // set the owning side to null (unless already changed)
            if ($submission->getStudent() === $this) {
                $submission->setStudent(null);
            }
        }

        return $this;
    }
}
