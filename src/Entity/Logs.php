<?php

namespace App\Entity;

use App\Repository\LogsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogsRepository::class)]
/**
 * Historique de connexion des utilisateurs.
 * Big Brother is watching you ! (Ou juste pour la sécurité).
 */
class Logs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * L'adresse IP de connexion.
     */
    #[ORM\Column(length: 255)]
    private ?string $ip = null;

    /**
     * La date et l'heure de la connexion.
     */
    #[ORM\Column]
    private ?\DateTime $loginAt = null;

    /**
     * Le navigateur utilisé (Chrome, Firefox, etc.).
     */
    #[ORM\Column(length: 255)]
    private ?string $userAgent = null;

    #[ORM\ManyToOne(inversedBy: 'logs')]
    private ?User $User = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getLoginAt(): ?\DateTime
    {
        return $this->loginAt;
    }

    public function setLoginAt(\DateTime $loginAt): static
    {
        $this->loginAt = $loginAt;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): static
    {
        $this->User = $User;

        return $this;
    }
}
