<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_COURSE_CODE', fields: ['code'])]
#[UniqueEntity('code', message: 'Данный код уже используется.')]
class Course
{
    public const TYPES = [
        'RENT' => 1,
        'FULL' => 2,
        'FREE' => 3,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Код курса не может быть пустым.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Код курса не может быть длиннее {{ limit }} символов.'
    )]
    #[Groups(['course:item'])]
    private ?string $code = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: 'Тип курса не может быть пустым.')]
    #[Assert\Choice(
        choices: self::TYPES,
        message: 'Некорректный тип курса.'
    )]
    #[Groups(['course:item'])]
    private ?int $type = null;

    #[ORM\Column(options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'Стоимость курса не может быть отрицательной.')]
    #[Groups(['course:item'])]
    private ?int $price = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
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

    public function getPriceInt(): ?int
    {
        return $this->price;
    }

    public function getPrice(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        return number_format($this->price / 100, 2, '.', '');
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }
}
