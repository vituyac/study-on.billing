<?php

namespace App\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CourseControllerTest extends WebTestCase
{
    public function testIndex()
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
            '/api/v1/courses',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseIsSuccessful();

        $courses = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($courses);
        $this->assertCount(4, $courses);
    }

    public function testShow()
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
            '/api/v1/courses/php-basics',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseIsSuccessful();

        $course = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($course);
        $this->assertSame('PHP для начинающих', $course['title']);
        $this->assertSame('php-basics', $course['code']);
        $this->assertSame('RENT', $course['type']);
        $this->assertSame('100.00', $course['price']);
    }

    public function testSuccessPay()
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
            'POST',
            '/api/v1/courses/symfony-start/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($response);
        $this->assertSame(true, $response['success']);
        $this->assertSame('FULL', $response['courseType']);

        $client->request(
            'GET',
            '/api/v1/users/current',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseIsSuccessful();

        $user = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('1300.00', $user['balance']);
    }

    #[DataProvider('invalidPayDataProvider')]
    public function testUnsuccessPay(array $formData, string $expectedError, int $statusCode): void
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
            'POST',
            '/api/v1/courses/' . $formData['courseCode'] . '/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseStatusCodeSame($statusCode);

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($response);
        $this->assertSame($statusCode, $response['code']);
        $this->assertSame($expectedError, $response['message']);
    }

    #[DataProvider('invalidPayDataProvider')]
    public static function invalidPayDataProvider(): iterable
    {
        yield 'not enough balance' => [[
            'courseCode' => 'web-security',
        ], 'На вашем счету недостаточно средств', 406];

        yield 'course already paid' => [[
            'courseCode' => 'php-basics',
        ], 'Курс уже оплачен', 409];
    }
}
