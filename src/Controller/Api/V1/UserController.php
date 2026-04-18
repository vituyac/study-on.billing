<?php

namespace App\Controller\Api\V1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/v1/users', name: 'api_v1_users_', defaults: ['_format' => 'json'])]
final class UserController extends AbstractController
{
    #[Route('/current', name: 'current', methods: ['GET'])]
    #[OA\Get(
        summary: 'Текущий пользователь',
        tags: ['user'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Текущий пользователь',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user01@mail.ru'),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'ROLE_USER')
                        ),
                        new OA\Property(property: 'balance', type: 'string', example: '0.00'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Неверный или отсутствующий JWT токен',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'code', type: 'integer', example: 401),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Invalid JWT Token / JWT Token not found'
                        )
                    ]
                )
            ),
        ]
    )]
    #[Security(name: 'Bearer')]
    public function current(): JsonResponse
    {
        $user = $this->getUser();

        return new JsonResponse([
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'balance' => $user->getBalance(),
        ], Response::HTTP_OK);
    }
}

// use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
// use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

// public function __construct(
//     private JWTTokenManagerInterface $jwtManager,
//     private TokenStorageInterface $tokenStorageInterface,
// ) {
// }

// $decodedJwt = $this->jwtManager->decode($this->tokenStorageInterface->getToken());

// return new JsonResponse([
//     'email' => $decodedJwt['email'] ?? null,
//     'roles' => $decodedJwt['roles'] ?? [],
//     'balance' => $decodedJwt['balance'] ?? null,
// ], Response::HTTP_OK);
