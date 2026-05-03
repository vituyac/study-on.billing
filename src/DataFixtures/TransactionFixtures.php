<?php

namespace App\DataFixtures;

use App\Entity\Transaction;
use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TransactionFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CourseRepository $courseRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $course = $this->courseRepository->findOneBy(['code' => 'php-basics']);
        $user = $this->userRepository->findOneBy(['email' => 'user01@mail.ru']);

        $transactionsData = [
            [
                'type' => Transaction::TYPES['PAYMENT'],
                'amount' => 10000,
                'course' => $course,
                'expiresAt' => (new \DateTimeImmutable())->modify('+7 days'),
                'customer' => $user,
            ],
        ];

        foreach ($transactionsData as $transactionData) {
            $transaction = new Transaction();
            $transaction->setType($transactionData['type']);
            $transaction->setAmount($transactionData['amount']);
            $transaction->setCourse($transactionData['course']);
            $transaction->setExpiresAt($transactionData['expiresAt']);
            $transaction->setCustomer($transactionData['customer']);
            $manager->persist($transaction);
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
