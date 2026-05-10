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

    public function testCreate(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'user02@mail.ru',
                'password' => 'password',
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('token', $data);

        $token = $data['token'];

        $client->request(
            'POST',
            '/api/v1/courses',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'code' => 'new-course',
                'title' => 'Новый курс',
                'type' => 'RENT',
                'price' => '100.00',
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertSame(201, $data['code']);
        $this->assertSame(true, $data['success']);
    }

    #[DataProvider('invalidCourseDataProvider')]
    public function testCreateValidation(array $formData, string $expectedError): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'user02@mail.ru',
                'password' => 'password',
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('token', $data);

        $token = $data['token'];

        $client->request(
            'POST',
            '/api/v1/courses',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'code' => $formData['code'],
                'title' => $formData['title'],
                'type' => $formData['type'],
                'price' => $formData['price'],
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertSame(422, $data['status']);
        $this->assertStringContainsString($expectedError, $data['detail']);
    }

    public function testUpdate(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'user02@mail.ru',
                'password' => 'password',
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('token', $data);

        $token = $data['token'];

        $client->request(
            'POST',
            '/api/v1/courses/php-basics',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'code' => 'php-basics',
                'title' => 'Новый курс',
                'type' => 'RENT',
                'price' => '100.00',
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertSame(200, $data['code']);
        $this->assertSame(true, $data['success']);
    }

    #[DataProvider('invalidCourseDataProvider')]
    public function testUpdateValidation(array $formData, string $expectedError): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'user02@mail.ru',
                'password' => 'password',
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('token', $data);

        $token = $data['token'];

        $client->request(
            'POST',
            '/api/v1/courses/php-basics',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($formData, JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertSame(422, $data['status']);
        $this->assertStringContainsString($expectedError, $data['detail']);
    }

    public static function invalidPayDataProvider(): iterable
    {
        yield 'not enough balance' => [[
            'courseCode' => 'web-security',
        ], 'На вашем счету недостаточно средств', 406];

        yield 'course already paid' => [[
            'courseCode' => 'php-basics',
        ], 'Курс уже оплачен', 409];
    }

    public static function invalidCourseDataProvider(): iterable
    {
        yield 'unique code' => [[
            'code' => 'web-security',
            'title' => 'Новый курс',
            'type' => 'RENT',
            'price' => '100.00',
        ], 'Данный код уже используется.'];

        yield 'empty code' => [[
            'code' => '',
            'title' => 'Новый курс',
            'type' => 'RENT',
            'price' => '100.00',
        ], 'Код курса не может быть пустым.'];

        yield 'empty title' => [[
            'code' => 'new-course',
            'title' => '',
            'type' => 'RENT',
            'price' => '100.00',
        ], 'Введите название курса.'];

        yield 'empty type' => [[
            'code' => 'new-course',
            'title' => 'Новый курс',
            'type' => '',
            'price' => '100.00',
        ], 'Тип курса не может быть пустым.'];

        yield 'invalid type' => [[
            'code' => 'new-course',
            'title' => 'Новый курс',
            'type' => 'INVALID',
            'price' => '100.00',
        ], 'Некорректный тип курса.'];

        yield 'long code' => [[
            'code' => str_repeat('a', 256),
            'title' => 'Новый курс',
            'type' => 'RENT',
            'price' => '100.00',
        ], 'Код курса не может быть длиннее 255 символов.'];

        yield 'long title' => [[
            'code' => 'new-course',
            'title' => str_repeat('a', 256),
            'type' => 'RENT',
            'price' => '100.00',
        ], 'Название курса должно быть не длиннее 255 символов.'];

        yield 'negative price' => [[
            'code' => 'new-course',
            'title' => 'Новый курс',
            'type' => 'RENT',
            'price' => '-100.00',
        ], 'Стоимость курса должна быть строкой в формате: 100.00.'];
    }
}
