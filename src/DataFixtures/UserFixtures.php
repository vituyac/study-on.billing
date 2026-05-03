<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\PaymentService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private readonly PaymentService $paymentService
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $usersData = [
            [
                'username' => 'user01@mail.ru',
                'password' => 'password',
                'roles' => ['ROLE_USER'],
                'balance' => 16000,
            ],
            [
                'username' => 'user02@mail.ru',
                'password' => 'password',
                'roles' => ['ROLE_SUPER_ADMIN'],
                'balance' => 0,
            ],
        ];

        foreach ($usersData as $userData) {
            $user = new User();
            $user->setEmail($userData['username']);
            $user->setPassword($this->hasher->hashPassword($user, $userData['password']));
            $user->setRoles($userData['roles']);

            $manager->persist($user);
            $manager->flush();

            if ($userData['balance'] > 0) {
                $this->paymentService->deposit($user, $userData['balance']);
            }
        }
    }
}
