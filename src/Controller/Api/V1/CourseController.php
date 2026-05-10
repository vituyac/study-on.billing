<?php

namespace App\Controller\Api\V1;

use App\Dto\Api\V1\CourseCreateDto;
use App\Dto\Api\V1\CourseUpdateDto;
use App\Entity\Course;
use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[Route('/api/v1', name: 'api_v1_', defaults: ['_format' => 'json'])]
final class CourseController extends AbstractController
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly EntityManagerInterface $em,
        private readonly PaymentService $paymentService,
        private readonly TransactionRepository $transactionRepository,
    ) {
    }

    #[Route('/courses', name: 'course_index', methods: ['GET'])]
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
    public function index(): JsonResponse
    {
        $courses = $this->courseRepository->findAll();

        return $this->json($courses, Response::HTTP_OK, [], [
            'groups' => ['course:item'],
        ]);
    }

    #[Route('/courses', name: 'course_new', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[OA\Post(
        summary: 'Создание курса',
        tags: ['course'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'title', 'type', 'price'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: 'php-basics'),
                    new OA\Property(property: 'title', type: 'string', example: 'PHP Basics'),
                    new OA\Property(
                        property: 'type',
                        type: 'string',
                        enum: ['RENT', 'FULL', 'FREE'],
                        example: 'RENT'
                    ),
                    new OA\Property(property: 'price', type: 'string', example: '100.00'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Курс успешно создан',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', type: 'integer', example: 201),
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован'
            ),
            new OA\Response(
                response: 403,
                description: 'Недостаточно прав'
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибки валидации'
            ),
        ]
    )]
    public function create(#[MapRequestPayload] CourseCreateDto $dto): JsonResponse
    {
        $course = new Course();
        $course->setTitle($dto->title);
        $course->setCode($dto->code);
        $course->setType(Course::TYPES[$dto->type]);
        $course->setPrice((int) round(((float) $dto->price) * 100));

        $this->em->persist($course);
        $this->em->flush();

        return new JsonResponse([
            'code' => 201,
            'success' => true,
        ], Response::HTTP_CREATED);
    }

    #[Route('/courses/{code}', name: 'course_edit', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[OA\Post(
        summary: 'Редактирование курса',
        tags: ['course'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                description: 'Код курса',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                example: 'php-basics'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'title', 'type', 'price'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: 'php-advanced'),
                    new OA\Property(property: 'title', type: 'string', example: 'PHP Advanced'),
                    new OA\Property(
                        property: 'type',
                        type: 'string',
                        enum: ['RENT', 'FULL', 'FREE'],
                        example: 'FULL'
                    ),
                    new OA\Property(property: 'price', type: 'string', example: '150.00'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Курс успешно обновлён',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', type: 'integer', example: 200),
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован'
            ),
            new OA\Response(
                response: 403,
                description: 'Недостаточно прав'
            ),
            new OA\Response(
                response: 404,
                description: 'Курс не найден'
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибки валидации'
            ),
        ]
    )]
    public function edit(
        #[MapEntity(expr: 'repository.findOneBy({"code": code})')] Course $course,
        #[MapRequestPayload] CourseUpdateDto $dto
    ): JsonResponse {
        if ($dto->code !== $course->getCode()) {
            $usedCode = $this->courseRepository->findOneBy(['code' => $dto->code]);
            if ($usedCode) {
                $violations = new ConstraintViolationList([
                    new ConstraintViolation(
                        message: 'Данный код уже используется.',
                        messageTemplate: 'Данный код уже используется.',
                        parameters: [],
                        root: $dto,
                        propertyPath: 'code',
                        invalidValue: $dto->code,
                    ),
                ]);

                throw new UnprocessableEntityHttpException(
                    message: 'Validation Failed',
                    previous: new ValidationFailedException($dto, $violations)
                );
            }
        }

        $course->setTitle($dto->title);
        $course->setCode($dto->code);
        $course->setType(Course::TYPES[$dto->type]);
        if ($dto->price !== null) {
            $course->setPrice((int) round(((float) $dto->price) * 100));
        }

        $this->em->flush();

        return new JsonResponse([
            'code' => 200,
            'success' => true,
        ], Response::HTTP_OK);
    }

    #[Route('/courses/{code}', name: 'course_show', methods: ['GET'])]
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

    #[Route('/courses/{code}/pay', name: 'courses_pay', methods: ['POST'])]
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

        $response = [
            'success' => true,
            'courseType' => $course->getType(),
        ];

        if ($course->getType() === 'FREE') {
            return new JsonResponse($response, Response::HTTP_OK);
        }

        $pastTransactions = $this->transactionRepository->findByFilters(
            $user,
            ['type' => 'PAYMENT', 'course_code' => $course->getCode(), 'skip_expired' => true]
        );

        if (!empty($pastTransactions)) {
            return new JsonResponse(['code' => 409, 'message' => 'Курс уже оплачен'], 409);
        }

        try {
            $transaction = $this->paymentService->pay($user, $course);
        } catch (\DomainException) {
            return new JsonResponse(['code' => 406, 'message' => 'На вашем счету недостаточно средств'], 406);
        }

        if ($course->getType() === 'RENT') {
            $response['expiresAt'] = $transaction->getExpiresAt();
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }
}
