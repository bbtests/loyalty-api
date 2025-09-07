<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait ApiResponseTrait
{
    /**
     * Success response for collections with pagination
     *
     * @template TValue
     *
     * @param  LengthAwarePaginator<int, TValue>  $paginator
     * @param  class-string  $resourceClass
     * @param  array<string, mixed>  $meta
     */
    protected function successCollection(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        string $message = 'Data fetched successfully.',
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $message,
            'data' => [
                'items' => $resourceClass::collection($paginator->items()),
            ],
            'errors' => [],
            'meta' => \array_merge([
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ], $meta),
        ]);
    }

    /**
     * Success response for simple collections (no pagination)
     *
     * @template TValue
     *
     * @param  Collection<int, TValue>  $items
     * @param  class-string  $resourceClass
     * @param  array<string, mixed>  $meta
     */
    protected function successItems(
        Collection $items,
        string $resourceClass,
        string $message = 'Data fetched successfully.',
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $message,
            'data' => [
                'items' => $resourceClass::collection($items),
            ],
            'errors' => [],
            'meta' => \array_merge(['pagination' => null], $meta),
        ]);
    }

    /**
     * Success response for single item
     *
     * @param  array<string, mixed>  $meta
     */
    protected function successItem(
        JsonResource $resource,
        string $message = 'Data retrieved successfully.',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'status' => 'success',
            'code' => $statusCode,
            'message' => $message,
            'data' => [
                'item' => $resource,
            ],
            'errors' => [],
            'meta' => \array_merge(['pagination' => null], $meta),
        ], $statusCode);
    }

    /**
     * Success response with custom data
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    protected function successData(
        array $data,
        string $message = 'Operation completed successfully.',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'status' => 'success',
            'code' => $statusCode,
            'message' => $message,
            'data' => $data,
            'errors' => [],
            'meta' => \array_merge(['pagination' => null], $meta),
        ], $statusCode);
    }

    /**
     * Success response with no data
     */
    protected function successMessage(
        string $message = 'Operation completed successfully.',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'status' => 'success',
            'code' => $statusCode,
            'message' => $message,
            'data' => ['item' => null],
            'errors' => [],
            'meta' => ['pagination' => null],
        ], $statusCode);
    }

    /**
     * Error response
     *
     * @param  array<int, array<string, mixed>|string|null>  $errors
     * @param  array<string, mixed>  $meta
     */
    protected function errorResponse(
        string $message,
        int $statusCode = 400,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'status' => 'error',
            'code' => $statusCode,
            'message' => $message,
            'data' => ['item' => null],
            'errors' => $errors,
            'meta' => \array_merge(['pagination' => null], $meta),
        ], $statusCode);
    }

    /**
     * Validation error response
     */
    protected function validationError(
        string $message,
        string $field,
        mixed $data = null
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            422,
            [
                [
                    'field' => $field,
                    'message' => $message,
                    'data' => $data,
                ],
            ]
        );
    }

    /**
     * Not found error response
     */
    protected function notFoundError(string $resource, string $id): JsonResponse
    {
        return $this->errorResponse(
            "{$resource} not found.",
            404,
            [
                [
                    'field' => 'id',
                    'message' => "No {$resource} with ID '{$id}' exists.",
                ],
            ]
        );
    }

    /**
     * Build meta filters from request
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, array<string, mixed>>
     */
    protected function buildFilters(array $filters): array
    {
        return ['filters' => \array_filter($filters, fn ($value) => $value !== null)];
    }
}
