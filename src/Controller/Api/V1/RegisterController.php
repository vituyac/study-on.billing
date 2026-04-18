<?php

namespace App\Controller\Api\V1;

use App\Dto\Api\V1\UserDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_', defaults: ['_format' => 'json'])]
final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly JWTTokenManagerInterface $tokenManager,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    #[OA\Post(
        summary: 'Регистрация',
        tags: ['user'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Данные для регистрации',
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
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Пользователь успешно создан',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['ROLE_USER']
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибки валидации'
            ),
        ]
    )]
    public function register(#[MapRequestPayload] UserDto $dto): JsonResponse
    {
        $user = new User();
        $user->setEmail($dto->email);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->hasher->hashPassword($user, $dto->password));

        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse([
            'token' => $this->tokenManager->create($user),
            'roles' => $user->getRoles(),
        ], Response::HTTP_CREATED);
    }
}
