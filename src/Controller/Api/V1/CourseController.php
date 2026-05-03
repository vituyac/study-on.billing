<?php

namespace App\Controller\Api\V1;

use App\Entity\Course;
use App\Service\PaymentService;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1', name: 'api_v1_', defaults: ['_format' => 'json'])]
final class CourseController extends AbstractController
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly EntityManagerInterface $em,
        private readonly PaymentService $paymentService,
    ) {
    }

    #[Route('/courses', name: 'courses', methods: ['GET'])]
    #[OA\Get(
        summary: 'Список курсов',
        tags: ['course'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список курсов',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'code', type: 'string', example: 'php-basics'),
                            new OA\Property(property: 'type', type: 'string', example: 'RENT'),
                            new OA\Property(property: 'price', type: 'string', example: '100.00'),
                        ],
                        type: 'object'
                    )
                )
            ),
        ]
    )]
    public function list(): JsonResponse
    {
        $courses = $this->courseRepository->findAll();

        return $this->json($courses, Response::HTTP_OK, [], [
            'groups' => ['course:item'],
        ]);
    }

    #[Route('/courses/{code}', name: 'course', methods: ['GET'])]
    #[OA\Get(
        summary: 'Подробнее о курсе',
        tags: ['course'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                description: 'Символьный код курса',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                example: 'php-basics'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Информация о курсе',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', type: 'string', example: 'php-basics'),
                        new OA\Property(property: 'type', type: 'string', example: 'RENT'),
                        new OA\Property(property: 'price', type: 'string', example: '100.00'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Курс не найден'
            ),
        ]
    )]
    public function show(#[MapEntity(expr: 'repository.findOneBy({"code": code})')] Course $course): JsonResponse
    {
        return $this->json($course, Response::HTTP_OK, [], [
            'groups' => ['course:item'],
        ]);
    }

    #[Route('/courses/{code}/pay', name: 'course_pay', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Post(
        summary: 'Оплата курса',
        security: [['Bearer' => []]],
        tags: ['course'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                description: 'Символьный код курса',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                example: 'php-basics'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Курс успешно оплачен',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'courseType', type: 'string', example: 'RENT'),
                        new OA\Property(
                            property: 'expiresAt',
                            type: 'string',
                            format: 'date-time',
                            nullable: true,
                            example: '2026-06-02T12:00:00+00:00'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован'
            ),
            new OA\Response(
                response: 404,
                description: 'Курс не найден'
            ),
            new OA\Response(
                response: 406,
                description: 'Недостаточно средств',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', type: 'integer', example: 406),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'На вашем счету недостаточно средств'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function pay(#[MapEntity(expr: 'repository.findOneBy({"code": code})')] Course $course): JsonResponse
    {
        $user = $this->getUser();

        try {
            $transaction = $this->paymentService->pay($user, $course);
        } catch (\DomainException) {
            return new JsonResponse(['code' => 406, 'message' => 'На вашем счету недостаточно средств'], 406);
        }

        $response = [
            'success' => true,
            'courseType' => $course->getType(),
        ];

        if ($course->getType() === 'RENT') {
            $response['expiresAt'] = $transaction->getExpiresAt()?->format(DATE_ATOM);
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }
}
