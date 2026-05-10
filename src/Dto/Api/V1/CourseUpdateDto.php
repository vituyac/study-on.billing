<?php

namespace App\Dto\Api\V1;

use Symfony\Component\Validator\Constraints as Assert;

class CourseUpdateDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Введите название курса.')]
        #[Assert\Length(
            max: 255,
            maxMessage: 'Название курса должно быть не длиннее {{ limit }} символов.'
        )]
        public readonly ?string $title = null,
        #[Assert\NotBlank(message: 'Код курса не может быть пустым.')]
        #[Assert\Length(
            max: 255,
            maxMessage: 'Код курса не может быть длиннее {{ limit }} символов.'
        )]
        public readonly ?string $code = null,
        #[Assert\NotBlank(message: 'Тип курса не может быть пустым.')]
        #[Assert\Choice(
            choices: ['RENT', 'FULL', 'FREE'],
            message: 'Некорректный тип курса.'
        )]
        public readonly ?string $type = null,
        #[Assert\Regex(
            pattern: '/^\d+(\.\d{2})?$/',
            message: 'Стоимость курса должна быть строкой в формате: 100.00.'
        )]
        public readonly ?string $price = null
    ) {
    }
}
