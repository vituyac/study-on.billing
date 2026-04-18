<?php

namespace App\Tests;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testAuthUser(): void
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
        $jwtEncoder = static::getContainer()->get(JWTEncoderInterface::class);
        $payload = $jwtEncoder->decode($token);
        $this->assertSame('user01@mail.ru', $payload['username']);
    }

    #[DataProvider('invalidUserDataProvider')]
    public function testAuthUserValidation(array $formData, string $expectedError, int $code): void
    {
        $payload = [];

        if ($formData['email'] !== null) {
            $payload['email'] = $formData['email'];
        }

        if ($formData['password'] !== null) {
            $payload['password'] = $formData['password'];
        }

        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame($code);

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString($expectedError, $data['message'] ?? $data['detail']);
        $this->assertStringContainsString($code, $data['code'] ?? $data['status']);
    }

    public static function invalidUserDataProvider(): iterable
    {
        yield 'invalid email or password' => [[
            'email' => 'invalid_user@mail.ru',
            'password' => 'passwordfsefsef',
        ], 'Invalid credentials.', 401];

        yield 'empty email' => [[
            'email' => '',
            'password' => 'password',
        ], 'The key "email" must be a non-empty string.', 400];

        yield 'empty password' => [[
            'email' => 'user01@mail.ru',
            'password' => '',
        ], 'The key "password" must be a non-empty string.', 400];

        yield 'null email' => [[
            'email' => null,
            'password' => 'password',
        ], 'The key "email" must be provided.', 400];

        yield 'null password' => [[
            'email' => 'user01@mail.ru',
            'password' => null,
        ], 'The key "password" must be provided.', 400];
    }
}
