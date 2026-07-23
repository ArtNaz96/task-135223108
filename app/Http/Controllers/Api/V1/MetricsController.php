<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MetricsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'requests_total' => 0,
            'errors_total' => 0,
        ]);
    }
}
