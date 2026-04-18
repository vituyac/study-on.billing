<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class UserControllerTest extends WebTestCase
{
    public function testCurrentUser()
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
            '/api/v1/users/current',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        $this->assertResponseIsSuccessful();

        $currentUser = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('user01@mail.ru', $currentUser['email']);
        $this->assertSame(['ROLE_USER'], $currentUser['roles']);
        $this->assertSame('1.23', $currentUser['balance']);
    }

    #[DataProvider('invalidTokenDataProvider')]
    public function testCurrentUserWithInvalidToken(array $formData, string $expectedError): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/api/v1/users/current',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $formData['token']]
        );
        $this->assertResponseStatusCodeSame(401);

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString($expectedError, $data['message']);
    }

    public static function invalidTokenDataProvider(): iterable
    {
        yield 'invalid token' => [[
            'token' => 'invalidToken',
        ], 'Invalid JWT Token'];

        yield 'empty token' => [[
            'token' => '',
        ], 'JWT Token not found'];
    }
}
