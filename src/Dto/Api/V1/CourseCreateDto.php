<?php

namespace App\Dto\Api\V1;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Course;

#[UniqueEntity(fields: ['code'], entityClass: Course::class, message: 'Данный код уже используется.')]
class CourseCreateDto extends CourseUpdateDto
{
}
