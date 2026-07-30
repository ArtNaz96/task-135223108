<?php

namespace Tests\Unit;

use App\Models\Feedback;
use App\Models\FeedbackRecord;
use App\Repositories\DatabaseFeedbackRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DatabaseFeedbackRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_saves_feedback_dto_to_database_table(): void
    {
        $repository = new DatabaseFeedbackRepository();

        $feedback = new Feedback(
            id: '20260729120000-112233',
            name: 'Алексей Смирнов',
            phone: '+79991234567',
            email: 'alexey@example.com',
            comment: 'Тестовое обращение для БД'
        );

        $repository->save($feedback);

        $this->assertDatabaseHas('feedback', [
            'id' => '20260729120000-112233',
            'name' => 'Алексей Смирнов',
            'phone' => '+79991234567',
            'email' => 'alexey@example.com',
            'comment' => 'Тестовое обращение для БД',
        ]);
    }
}