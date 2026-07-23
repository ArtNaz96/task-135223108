<?php

namespace App\Services;

use App\Models\Feedback;
use App\Repositories\FeedbackRepositoryInterface;

class FeedbackProcessingService
{
    public function __construct(
        private readonly FeedbackRepositoryInterface $feedbackRepository,
        private readonly AiProcessingService $aiProcessingService,
    ) {}

    public function process(array $data): Feedback
    {
        $feedback = new Feedback(
            id: $this->generateId(),
            name: $data['name'],
            phone: $data['phone'],
            email: $data['email'],
            comment: $data['comment'],
        );

        $this->feedbackRepository->save($feedback);

        $this->aiProcessingService->process($feedback);

        return $feedback;
    }

    private function generateId(): string
    {
        return now()->format('YmdHis') . '-' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
