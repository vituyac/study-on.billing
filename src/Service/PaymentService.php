<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PaymentService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function deposit(User $user, int $amount): Transaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException();
        }

        return $this->em->wrapInTransaction(function () use ($user, $amount): Transaction {
            $transaction = new Transaction();
            $transaction->setCustomer($user);
            $transaction->setType(Transaction::TYPES['DEPOSIT']);
            $transaction->setAmount($amount);

            $user->setBalance(($user->getBalanceInt() ?? 0) + $amount);

            $this->em->persist($transaction);

            return $transaction;
        });
    }

    public function pay(User $user, Course $course): Transaction
    {
        if (($user->getBalanceInt() ?? 0) < $course->getPriceInt()) {
            throw new \DomainException();
        }

        return $this->em->wrapInTransaction(function () use ($user, $course): Transaction {
            $transaction = new Transaction();
            $transaction->setCustomer($user);
            $transaction->setCourse($course);
            $transaction->setType(Transaction::TYPES['PAYMENT']);
            $transaction->setAmount($course->getPriceInt());

            if ($course->getType() === 'RENT') {
                $transaction->setExpiresAt((new \DateTimeImmutable())->modify('+7 days'));
            }

            $user->setBalance(($user->getBalanceInt() ?? 0) - ($course->getPriceInt() ?? 0));

            $this->em->persist($transaction);

            return $transaction;
        });
    }
}
