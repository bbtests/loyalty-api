<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use ApiResponseTrait, AuthorizesRequests;

    /**
     * Apply common pagination parameters
     *
     * @return array<string, mixed>
     */
    public static function applyPagination(Request $request): array
    {
        return [
            'per_page' => \min(
                $request->input('per_page', config('constants.pagination.default_per_page', 15)),
                config('constants.pagination.max_per_page', 100)
            ),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];
    }
}
