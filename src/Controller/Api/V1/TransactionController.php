<?php

namespace App\Controller\Api\V1;

use App\Repository\TransactionRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1', name: 'api_v1_', defaults: ['_format' => 'json'])]
final class TransactionController extends AbstractController
{
    public function __construct(
        private readonly TransactionRepository $transactionRepository,
    ) {
    }

    #[Route('/transactions', name: 'transactions', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        summary: 'История транзакций',
        security: [['Bearer' => []]],
        tags: ['transaction'],
        parameters: [
            new OA\Parameter(
                name: 'filter[type]',
                description: 'Тип транзакции',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['PAYMENT', 'DEPOSIT']),
            ),
            new OA\Parameter(
                name: 'filter[course_code]',
                description: 'Символьный код курса',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'filter[skip_expired]',
                description: 'Не показывать истекшие аренды',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean'),
                example: true
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Транзакции',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 11),
                            new OA\Property(
                                property: 'createdAt',
                                type: 'string',
                                format: 'date-time',
                                example: '2019-05-20T13:46:07+00:00'
                            ),
                            new OA\Property(property: 'type', type: 'string', example: 'PAYMENT'),
                            new OA\Property(
                                property: 'courseCode',
                                type: 'string',
                                nullable: true,
                                example: 'php-basics'
                            ),
                            new OA\Property(property: 'amount', type: 'string', example: '100.00'),
                        ],
                        type: 'object'
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован'
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $this->getUser();

        $filters = $request->query->all('filter');

        $transactions = $this->transactionRepository->findByFilters($user, $filters);

        return $this->json($transactions, Response::HTTP_OK, [], [
            'groups' => ['transaction:item'],
        ]);
    }
}
