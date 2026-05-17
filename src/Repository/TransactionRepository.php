<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findByFilters(User $user, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.customer = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC');

        if (!empty($filters['type'])) {
            $type = strtoupper($filters['type']);

            if (isset(Transaction::TYPES[$type])) {
                $qb
                    ->andWhere('t.type = :type')
                    ->setParameter('type', Transaction::TYPES[$type]);
            }
        }

        if (!empty($filters['course_code'])) {
            $qb
                ->join('t.course', 'c')
                ->andWhere('c.code = :courseCode')
                ->setParameter('courseCode', $filters['course_code']);
        }

        if (!empty($filters['skip_expired'])) {
            $qb
                ->andWhere('t.expiresAt IS NULL OR t.expiresAt > :now')
                ->setParameter('now', new \DateTimeImmutable());
        }

        return $qb->getQuery()->getResult();
    }

    public function findRentEndingBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.course', 'c')
            ->andWhere('t.type = :transactionType')
            ->andWhere('c.type = :courseType')
            ->andWhere('t.expiresAt IS NOT NULL')
            ->andWhere('t.expiresAt BETWEEN :from AND :to')
            ->setParameter('transactionType', Transaction::TYPES['PAYMENT'])
            ->setParameter('courseType', Course::TYPES['RENT'])
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    public function getPaidCoursesReport(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('t')
            ->select('c.title AS courseTitle')
            ->addSelect('c.type AS courseType')
            ->addSelect('COUNT(t.id) AS purchasesCount')
            ->addSelect('SUM(t.amount) AS totalAmount')
            ->join('t.course', 'c')
            ->andWhere('t.type = :type')
            ->andWhere('t.createdAt BETWEEN :from AND :to')
            ->setParameter('type', Transaction::TYPES['PAYMENT'])
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('c.id')
            ->addGroupBy('c.type')
            ->getQuery()
            ->getArrayResult();
    }
//    /**
//     * @return Transaction[] Returns an array of Transaction objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Transaction
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
