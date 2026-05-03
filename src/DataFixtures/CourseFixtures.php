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
                'code' => 'php-basics',
                'type' => Course::TYPES['RENT'],
                'price' => 10000,
            ],
            [
                'code' => 'symfony-start',
                'type' => Course::TYPES['FULL'],
                'price' => 20000,
            ],
            [
                'code' => 'doctrine-practice',
                'type' => Course::TYPES['FREE'],
                'price' => 0,
            ],
            [
                'code' => 'web-security',
                'type' => Course::TYPES['FULL'],
                'price' => 15000,
            ],
        ];

        foreach ($coursesData as $courseData) {
            $course = new Course();
            $course->setCode($courseData['code']);
            $course->setType($courseData['type']);
            $course->setPrice($courseData['price']);

            $manager->persist($course);
        }

        $manager->flush();
    }
}
