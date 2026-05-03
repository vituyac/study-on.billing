<?php

namespace App\DataFixtures;

use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use App\Service\PaymentService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TransactionFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CourseRepository $courseRepository,
        private readonly PaymentService $paymentService
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $course = $this->courseRepository->findOneBy(['code' => 'php-basics']);
        $user = $this->userRepository->findOneBy(['email' => 'user01@mail.ru']);

        $transactionsData = [
            [
                'course' => $course,
                'customer' => $user,
            ]
        ];

        foreach ($transactionsData as $transactionData) {
            $this->paymentService->pay($transactionData['customer'], $transactionData['course']);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CourseFixtures::class,
        ];
    }
}
