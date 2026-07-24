<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MetricsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'message' => 'Not implemented yet',
        ], 501);
    }
}
