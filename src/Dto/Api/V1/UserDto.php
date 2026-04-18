<?php

namespace App\Dto\Api\V1;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\User;

#[UniqueEntity(fields: ['email'], entityClass: User::class, message: 'Данный email уже занят.')]
class UserDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email обязателен.')]
        #[Assert\Email(message: 'Некорректный email.')]
        public readonly ?string $email = null,
        #[Assert\NotBlank(message: 'Пароль обязателен.')]
        #[Assert\Length(min: 6, minMessage: 'Пароль должен быть не менее {{ limit }} символов.')]
        public readonly ?string $password = null,
    ) {
    }
}
