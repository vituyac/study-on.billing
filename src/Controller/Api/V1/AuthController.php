<?php

namespace App\Controller\Api\V1;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_', defaults: ['_format' => 'json'])]
final class AuthController extends AbstractController
{
    #[Route('/auth', name: 'auth', methods: ['POST'])]
    #[OA\Post(
        summary: 'Авторизация',
        tags: ['user'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        example: 'user01@mail.ru'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        example: 'password'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'jwt',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'token',
                            type: 'string',
                            example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Неверное имя пользователя или пароль',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'code', type: 'integer', example: 401),
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid credentials.')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Неверный формат запроса',
            ),
        ]
    )]
    public function auth(): JsonResponse
    {
        throw new \LogicException('Endpoint is not callable.');
    }
}
