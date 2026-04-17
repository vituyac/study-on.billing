<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $usersData = [
            [
                'username' => 'user01@mail.ru',
                'password' => 'password',
                'roles' => ['ROLE_USER'],
                'balance' => '1.23',
            ],
            [
                'username' => 'user02@mail.ru',
                'password' => 'password',
                'roles' => ['ROLE_SUPER_ADMIN'],
                'balance' => '1.27',
            ],
        ];

        foreach ($usersData as $userData) {
            $user = new User();
            $user->setEmail($userData['username']);
            $user->setPassword($this->hasher->hashPassword($user, $userData['password']));
            $user->setRoles($userData['roles']);
            $user->setBalance($userData['balance']);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
