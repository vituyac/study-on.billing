<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TransactionControllerTest extends WebTestCase
{
    #[DataProvider('transactionFiltersDataProvider')]
    public function testListWithFilters(array $filters, int $expectedCount, ?array $expectedFirst = null): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'user01@mail.ru',
                'password' => 'password',
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('token', $data);

        $token = $data['token'];

        $client->request(
            'GET',
            '/api/v1/transactions',
            $filters,
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseIsSuccessful();

        $transactions = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($transactions);
        $this->assertCount($expectedCount, $transactions);

        if ($expectedFirst !== null) {
            $transaction = $transactions[0];

            foreach ($expectedFirst as $field => $value) {
                $this->assertSame($value, $transaction[$field]);
            }
        }
    }

    #[DataProvider('transactionFiltersDataProvider')]
    public static function transactionFiltersDataProvider(): iterable
    {
        yield 'without filters' => [[], 2, null];

        yield 'filter by type PAYMENT' => [
            [
                'filter' => ['type' => 'PAYMENT'],
            ],
            1,
            [
                'type' => 'PAYMENT',
                'amount' => '100.00',
                'courseCode' => 'php-basics',
            ],
        ];

        yield 'filter by type DEPOSIT' => [
            [
                'filter' => ['type' => 'DEPOSIT'],
            ],
            1,
            [
                'type' => 'DEPOSIT',
                'amount' => '160.00',
            ],
        ];

        yield 'filter by course code' => [
            [
                'filter' => [
                    'course_code' => 'php-basics',
                ],
            ],
            1,
            [
                'courseCode' => 'php-basics',
            ],
        ];
    }
}
