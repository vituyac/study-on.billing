<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $coursesData = [
            [
                'title' => 'PHP для начинающих',
                'code' => 'php-basics',
                'type' => Course::TYPES['RENT'],
                'price' => 10000,
            ],
            [
                'title' => 'Быстрый старт с Symfony',
                'code' => 'symfony-start',
                'type' => Course::TYPES['FULL'],
                'price' => 20000,
            ],
            [
                'title' => 'Doctrine ORM на практике',
                'code' => 'doctrine-practice',
                'type' => Course::TYPES['FREE'],
                'price' => 0,
            ],
            [
                'title' => 'Основы безопасности веб-приложений',
                'code' => 'web-security',
                'type' => Course::TYPES['FULL'],
                'price' => 160000,
            ],
        ];

        foreach ($coursesData as $courseData) {
            $course = new Course();
            $course->setTitle($courseData['title']);
            $course->setCode($courseData['code']);
            $course->setType($courseData['type']);
            $course->setPrice($courseData['price']);

            $manager->persist($course);
        }

        $manager->flush();
    }
}
