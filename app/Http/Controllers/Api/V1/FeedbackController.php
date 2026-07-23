<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeedbackRequest;
use App\Services\FeedbackProcessingService;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackProcessingService $feedbackProcessingService
    ) {}

    public function __invoke(FeedbackRequest $request): JsonResponse
    {
        $feedback = $this->feedbackProcessingService->process($request->validated());

        return response()->json([
            'message' => 'Feedback successfully received',
            'data' => $feedback->toArray(),
        ], 201);
    }
}
