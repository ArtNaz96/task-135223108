<?php

namespace Tests\Unit;

use App\Mail\NewFeedbackMail;
use App\Models\Feedback;
use Tests\TestCase;

class NewFeedbackMailTest extends TestCase
{
    private function makeFeedback(string $comment = 'Строка первая'): Feedback
    {
        return new Feedback(
            id: '20260724120000-123456',
            name: 'Jane Doe',
            phone: '+1234567890',
            email: 'jane@example.com',
            comment: $comment,
        );
    }

    public function test_has_subject_with_request_id(): void
    {
        $mail = new NewFeedbackMail($this->makeFeedback());

        $this->assertSame('Запрос #20260724120000-123456', $mail->envelope()->subject);
    }

    public function test_renders_body_with_exact_template(): void
    {
        $feedback = $this->makeFeedback("Строка первая\nСтрока вторая");
        $mail = new NewFeedbackMail($feedback);

        $rendered = trim($mail->render());

        $expected = trim(
            "Ваш запрос #{$feedback->id} получен и принят в работу.\n"
            . "\n"
            . "Сообщение:\n"
            . "\n"
            . "{$feedback->comment}\n"
            . "\n"
            . "---\n"
            . "С уважением, команда сайта"
        );

        $this->assertSame($expected, $rendered);
    }

    public function test_body_contains_no_html_tags(): void
    {
        $mail = new NewFeedbackMail($this->makeFeedback());

        $this->assertDoesNotMatchRegularExpression('/<[a-z][\s\S]*>/i', $mail->render());
    }

    public function test_comment_is_rendered_escaped(): void
    {
        $mail = new NewFeedbackMail($this->makeFeedback('Tom & Jerry <3'));

        $this->assertStringContainsString('Tom &amp; Jerry &lt;3', $mail->render());
        $this->assertStringNotContainsString('Tom & Jerry <3', $mail->render());
    }
}
