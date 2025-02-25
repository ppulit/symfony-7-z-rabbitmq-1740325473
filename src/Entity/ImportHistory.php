<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ImportHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    private int $processed;

    #[ORM\Column]
    private int $errors;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessages;

    #[ORM\Column(length: 255)]
    private string $status;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct(int $processed, int $errors, string $status, ?string $errorMessages)
    {
        $this->processed = $processed;
        $this->errors = $errors;
        $this->status = $status;
        $this->createdAt = new \DateTime();
        $this->errorMessages = $errorMessages;
    }

    public function getProcessed(): int
    {
        return $this->processed;
    }

    public function getErrors(): int
    {
        return $this->errors;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getErrorMessages(): ?string
    {
        return $this->errorMessages;
    }
}
