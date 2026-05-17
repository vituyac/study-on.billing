<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccessControllerTest extends WebTestCase
{
    public function testCreateCourseByRoleUserOrUnauthorized(): void
    {
        $client = static::createClient();
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
        $this->assertResponseStatusCodeSame(401);

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
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateCourseByRoleUserOrUnauthorized(): void
    {
        $client = static::createClient();
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
                'code' => 'new-course',
                'title' => 'Новый курс',
                'type' => 'RENT',
                'price' => '100.00',
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(401);

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
            '/api/v1/courses/php-basics',
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
        $this->assertResponseStatusCodeSame(403);
    }
}
