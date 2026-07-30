<?php

namespace Tests\Unit;

use App\Models\FeedbackInsight;
use App\Models\Insight;
use App\Repositories\DatabaseFeedbackInsightRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseFeedbackInsightRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_saves_feedback_insight_to_database_table(): void
    {
        $repository = new DatabaseFeedbackInsightRepository();

        $insight = new FeedbackInsight(
            id: '20260729120000-112233',
            payload: [
                'summary' => 'Запрос стоимости разработки API',
                'sentiment' => 'positive',
                'category' => 'lead',
            ]
        );

        $repository->save($insight);

        $this->assertDatabaseHas('insights', [
            'id' => '20260729120000-112233',
        ]);

        $record = Insight::find('20260729120000-112233');
        $this->assertNotNull($record);
        $this->assertEquals('positive', $record->payload['sentiment']);
    }
}