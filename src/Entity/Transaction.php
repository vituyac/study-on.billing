<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    public const TYPES = [
        'DEPOSIT' => 1,
        'PAYMENT' => 2,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['transaction:item'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Пользователь не может быть пустым.')]
    private ?User $customer = null;

    #[ORM\ManyToOne]
    private ?Course $course = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotNull(message: 'Тип операции не может быть пустым.')]
    #[Assert\Choice(
        choices: self::TYPES,
        message: 'Некорректный тип операции.'
    )]
    #[Groups(['transaction:item'])]
    private ?int $type = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Значение транзакции не может быть пустым.')]
    #[Assert\PositiveOrZero(message: 'Значение транзакции не может быть отрицательным.')]
    #[Groups(['transaction:item'])]
    private ?int $amount = null;

    #[ORM\Column]
    #[Groups(['transaction:item'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    #[Groups(['transaction:item'])]
    #[SerializedName('courseCode')]
    public function getCourseCode(): ?string
    {
        return $this->course?->getCode();
    }

    // public function getType(): ?int
    // {
    //     return $this->type;
    // }

    public function getType(): ?string
    {
        return array_search($this->type, self::TYPES, true) ?: null;
    }

    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

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

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }
}
