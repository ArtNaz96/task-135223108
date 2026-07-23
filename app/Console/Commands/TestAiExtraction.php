<?php

namespace App\Console\Commands;

use App\Models\Feedback;
use App\Services\AiProcessingService;
use Illuminate\Console\Command;

/**
 * Ручная диагностика: прогоняет реальные примеры обращений через AiProcessingService
 * и настоящий AI Gateway (не мок). См. TESTING.md §5.
 */
class TestAiExtraction extends Command
{
    protected $signature = 'app:test-ai-extraction {--limit=3 : Сколько примеров прогнать (жёсткий максимум — 3)}';

    protected $description = 'Прогоняет реальные примеры обращений через AiProcessingService (ручная проверка интеграции с реальным AI Gateway)';

    /**
     * Жёсткий потолок — не зависит от --limit, чтобы случайно не задудосить реальный AI-провайдер.
     */
    private const MAX_SAMPLES = 3;

    /**
     * Реальные примеры из directives/AI/Письма клиентов (Support_Mock_Data).xlsx.
     */
    private const SAMPLE_TEXTS = [
        'Здравствуйте! Мой заказ 1001 задерживается уже на 3 дня. У кота заканчивается лечебный корм, скажите точную дату доставки!',
        'Купил лакомства, а они покрыты плесенью! Как такое вообще можно отправлять клиентам?!',
        'Добрый день. Посоветуйте, пожалуйста, какую-нибудь прочную игрушку для джек-рассела, он сгрызает всё за 5 минут.',
    ];

    public function handle(AiProcessingService $service): int
    {
        $limit = min((int) $this->option('limit'), self::MAX_SAMPLES);

        if ($limit < 1) {
            $this->error('Нечего прогонять: --limit должен быть от 1 до ' . self::MAX_SAMPLES);

            return self::FAILURE;
        }

        $texts = array_slice(self::SAMPLE_TEXTS, 0, $limit);

        foreach ($texts as $i => $text) {
            $feedback = new Feedback(
                id: 'TESTRUN-' . ($i + 1),
                name: 'Test Client',
                phone: '+70000000000',
                email: 'test@example.com',
                comment: $text,
            );

            $this->info("=== #{$feedback->id}: {$text}");
            $service->process($feedback);
        }

        $this->info('DONE — результат смотреть в storage/logs/feedback-insight.log (записи с id вида TESTRUN-N).');

        return self::SUCCESS;
    }
}
