<?php

namespace App\Tests;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegisterControllerTest extends WebTestCase
{
    public function testRegister()
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
            'email' => 'user03@mail.ru',
            'password' => 'password',
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('token', $data);
        $this->assertSame(['ROLE_USER'], $data['roles']);

        $token = $data['token'];
        $jwtEncoder = static::getContainer()->get(JWTEncoderInterface::class);
        $payload = $jwtEncoder->decode($token);
        $this->assertSame('user03@mail.ru', $payload['username']);
    }

    #[DataProvider('invalidUserDataProvider')]
    public function testRegisterValidation(array $formData, string $expectedError): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
            'email' => $formData['email'],
            'password' => $formData['password'],
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('violations', $data);
        $this->assertStringContainsString($expectedError, $data['violations'][0]['title']);
        $this->assertStringContainsString(422, $data['status']);
    }

    public static function invalidUserDataProvider(): iterable
    {
        yield 'email already exists' => [[
            'email' => 'user02@mail.ru',
            'password' => 'password',
        ], 'Данный email уже занят.'];

        yield 'invalid email' => [[
            'email' => 'invalid_email',
            'password' => 'password',
        ], 'Некорректный email.'];

        yield 'empty email' => [[
            'email' => '',
            'password' => 'password',
        ], 'Email обязателен.'];

        yield 'empty password' => [[
            'email' => 'user03@mail.ru',
            'password' => '',
        ], 'Пароль обязателен.'];

        yield 'short password' => [[
            'email' => 'user03@mail.ru',
            'password' => 'short',
        ], 'Пароль должен быть не менее 6 символов.'];
    }
}
