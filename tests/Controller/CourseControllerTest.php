<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CourseControllerTest extends WebTestCase
{
    public function testList()
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
            '/api/v1/courses/php-basics/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($response);
        $this->assertSame(true, $response['success']);
        $this->assertSame('RENT', $response['courseType']);

        $client->request(
            'GET',
            '/api/v1/users/current',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseIsSuccessful();

        $user = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('60.00', $user['balance']);
    }

    public function testUnsuccessPay()
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
        $this->assertResponseStatusCodeSame(406);

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($response);
        $this->assertSame(406, $response['code']);
        $this->assertSame('На вашем счету недостаточно средств', $response['message']);
    }
}
