<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeedbackRequest;
use App\Services\FeedbackService;
use App\Support\InputSanitizer;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackService $feedbackService
    ) {}

    public function __invoke(FeedbackRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['name'] = InputSanitizer::sanitize($data['name']);
        $data['comment'] = InputSanitizer::sanitize($data['comment']);

        $feedback = $this->feedbackService->process($data);

        return response()->json([
            'message' => 'Feedback successfully received',
            'data' => $feedback->toArray(),
        ], 201);
    }
}
