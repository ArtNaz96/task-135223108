<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeedbackRequest;
use App\Services\FeedbackProcessingService;
use App\Support\InputSanitizer;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackProcessingService $feedbackProcessingService
    ) {}

    public function __invoke(FeedbackRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['name'] = InputSanitizer::sanitize($data['name']);
        $data['comment'] = InputSanitizer::sanitize($data['comment']);

        $feedback = $this->feedbackProcessingService->process($data);

        return response()->json([
            'message' => 'Feedback successfully received',
            'data' => $feedback->toArray(),
        ], 201);
    }
}
