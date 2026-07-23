<?php

namespace App\Services;

use App\DTO\Feedback;
use App\Repositories\FeedbackRepositoryInterface;

class FeedbackProcessingService
{
    public function __construct(
        private readonly FeedbackRepositoryInterface $feedbackRepository,
        private readonly AiAnalysisService $aiAnalysisService
    ) {}

    public function process(array $data): Feedback
    {
        $analyzedComment = $this->aiAnalysisService->analyze($data['comment']);

        $feedback = new Feedback(
            name: $data['name'],
            phone: $data['phone'],
            email: $data['email'],
            comment: $analyzedComment
        );

        $this->feedbackRepository->save($feedback);

        return $feedback;
    }
}