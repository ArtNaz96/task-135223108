# Спецификация тестов

Дополняет `PRODUCT.md` (AC 6.2 — «написанные юнит-тесты успешно проходят без ошибок» не говорит, что именно нужно покрыть). Здесь — конкретно что, на каком уровне и как.

Пометки: **✅ написан** — тест существует в репозитории; без пометки — только запланирован (эту спеку соблюдаем при написании). Пути — фактические (плоская структура `tests/Unit/*.php`, без вложенных поддиректорий по слоям, за исключением `tests/Feature/Api/V1/`).

## 1. Уровни тестирования

* **Feature-тесты** (`tests/Feature/...`) — через реальный HTTP-слой (`Route`/`Controller`). Проверяют контракт эндпоинта: статус, тело ответа, побочные эффекты (лог, письмо).
* **Unit-тесты** (`tests/Unit/...`) — изолированно, без HTTP: сервисы, репозиторий, Mailable, генерация `id`.

## 2. Feature-тесты

### 2.1. `POST /api/v1/contact`
Сценарии разнесены по нескольким файлам (а не в одном `ContactControllerTest.php`):

| Тест | Файл | Сценарий | AC |
|---|---|---|---|
| `test_contact_form_rate_limiter_blocks_excessive_requests` | `tests/Feature/RateLimitingTest.php` ✅ | 6-й запрос с одного IP в минуту → 429 | AC 2.4, §7 |
| `test_allows_request_from_allowed_origin` / `test_blocks_request_from_disallowed_origin` / `test_blocks_request_when_port_does_not_match` | `tests/Feature/CorsTest.php` ✅ | CORS preflight по списку разрешённых origin'ов | — |
| `test_successful_ai_extraction_saves_feedback_insight` | `tests/Feature/Api/V1/ContactAiExtractionTest.php` ✅ | Валидный запрос → `AiGatewayInterface::extract()` вызван, `FeedbackInsightRepositoryInterface::save()` вызван | AC 3.1 |
| `test_ai_gateway_failure_does_not_break_the_request` | `tests/Feature/Api/V1/ContactAiExtractionTest.php` ✅ | AI-шлюз бросает исключение → запрос всё равно 201, `FeedbackInsightRepositoryInterface::save()` не вызван | AC 3.2 |
| `test_successful_request_sends_one_mail_to_owner_with_user_cc` | `tests/Feature/Api/V1/ContactMailTest.php` ✅ | Валидный запрос → 201, ровно одно письмо в очереди, `to` = `SITE_OWNER_EMAIL`, `cc` содержит `$feedback->email` | AC 4.1, AC 4.2 |
| `test_validation_failure_does_not_send_mail` | `tests/Feature/Api/V1/ContactMailTest.php` ✅ | Невалидный запрос → 422, писем не отправлено | AC 4.1/4.2 (негатив) |
| `it_accepts_valid_payload` | *(не написан)* | Валидные `name`/`phone`/`email`/`comment` → 201, тело содержит `message` и `data` | AC 1.1, AC 1.2 |
| `it_rejects_missing_required_field` | *(не написан)* | По очереди отсутствует каждое обязательное поле → 422 | AC 2.1, §7 |
| `it_rejects_invalid_email_format` | *(не написан)* | `email` некорректного формата → 422 | AC 2.2, §7 |
| `it_rejects_comment_longer_than_2000_chars` | *(не написан)* | `comment` > `FEEDBACK_COMMENT_MAX_LENGTH` («простыня») → 422 | AC 2.3, §7 |
| `it_returns_500_without_leaking_internals_on_unexpected_error` | *(не написан)* | Смоделированное исключение → 500, JSON без стектрейса, запись в `storage/logs/laravel.log` | AC 2.5, §7 |
| `it_writes_valid_feedback_to_feedback_storage_channel` | *(не написан)* | Валидный запрос → канал `feedback_storage` получает запись | AC 5.1 |
| `it_does_not_write_invalid_requests_to_feedback_storage_channel` | *(не написан)* | Невалидный запрос → канал `feedback_storage` не тронут | AC 5.1 (негатив) |

### 2.2. `GET /api/v1/health`
* `it_returns_ok_status` *(не написан)* — 200, JSON `{"status": "ok"}`.

### 2.3. `GET /api/v1/metrics`
* `it_returns_not_implemented` *(не написан)* — `501 Not Implemented` (см. `ARCHITECTURE.md` §4).

## 3. Unit-тесты

### 3.1. `tests/Unit/Support/InputSanitizerTest.php` *(не написан)*
* `it_strips_html_tags` — `<script>alert(1)</script>текст` → `текст`.
* `it_removes_control_characters_except_newline_tab_cr` — `\x00`/`\x1F`/`\x7F` удаляются, `\n`/`\r`/`\t` остаются.
* `it_preserves_line_breaks_in_multiline_comment` — переносы строк не схлопываются.
* `it_collapses_only_horizontal_whitespace` — повторяющиеся пробелы/табы схлопываются, переносы — нет.
* `it_trims_leading_and_trailing_whitespace_per_line_and_overall`.

### 3.2. `tests/Unit/FeedbackServiceTest.php` ✅ *(переименован из `FeedbackProcessingServiceTest.php`)*
* `test_process_saves_feedback_with_generated_id_and_unmodified_comment` — `id` формата `^\d{14}-\d{6}$`, `comment` не изменяется (AI не мутирует текст — см. §3.5).
* `test_process_calls_dependencies_in_correct_order` — порядок вызовов: `FeedbackRepositoryInterface::save()` → `AiProcessingService::process()` → `FeedbackNotifier::notify()` (важно для `SPECS-AI.md`: `FeedbackInsight` не может попасть в ту же запись `feedback_storage`).

### 3.3. `tests/Unit/AiProcessingServiceTest.php` ✅
См. `SPECS-AI.md`.
* `test_sends_comment_and_system_prompt_to_gateway`
* `test_saves_feedback_insight_on_successful_extraction`
* `test_does_not_save_feedback_insight_on_gateway_failure`
* `test_does_not_propagate_gateway_exception`

### 3.4. `tests/Unit/LogFeedbackRepositoryTest.php` ✅
* `test_save_logs_feedback_data_to_feedback_storage_channel` — пишет в канал `feedback_storage` (не `single`).

### 3.5. `tests/Unit/NewFeedbackMailTest.php` ✅
Содержание письма/шаблонизация — см. `SPECS-Mail.md`.
* `test_has_subject_with_request_id` — тема (рендер `subject.blade.php`) — `Запрос #<id>`.
* `test_renders_body_with_exact_template` — тело (рендер `body.blade.php`) посимвольно совпадает со спекой.
* `test_body_contains_no_html_tags` — несмотря на движок Blade, тело — чистый текст (`Content(text: ...)`, не `html`/`htmlString`).
* `test_comment_is_rendered_escaped` — `{{ $comment }}` в шаблоне (не `{!! !!}`) — только безопасный (экранированный) вывод, даже в plain-text части.

Адресация (`To`/`Cc`) у самого `Mailable` не проверяется — она задаётся снаружи через `Mail::to()->cc()`, см. §3.9/§2.1.

### 3.9. `tests/Unit/FeedbackNotifierTest.php` ✅
* `test_sends_mail_to_owner_with_user_cc` — `to()` = `services.site_owner.email` (`SITE_OWNER_EMAIL`), `cc()` = `$feedback->email`.
* `test_sends_exactly_one_mail` — ровно одно письмо в очереди на обращение.

### 3.6. `tests/Unit/OpenAiGatewayTest.php` ✅
* `test_calls_chat_completions_endpoint_with_expected_body_and_headers` — URL, `Authorization: Bearer {AI_GATEWAY_API_KEY}`, тело (`model`/`temperature: 0`/`response_format`/`messages`).
* `test_throws_on_invalid_json_response`
* `test_throws_when_request_fails`

### 3.7. `tests/Unit/LogFeedbackInsightRepositoryTest.php` ✅
* `test_save_writes_to_feedback_insight_storage_channel` — пишет в канал `feedback_insight_storage`, содержит `id` и `payload`.

## 4. Инструменты/подходы

* `Mail::fake()` + `Mail::assertSent(...)` — для писем, без реальной отправки.
* `Http::fake()` — для `OpenAiGateway`, без реальных запросов к AI-провайдеру.
* Для проверки записи в конкретный Monolog-канал — мок `Psr\Log\LoggerInterface` + `Log::shouldReceive('channel')->with('<имя канала>')->andReturn($mock)`.
* Для Feature-тестов, где нужно подменить AI-слой целиком — `$this->app->instance(AiGatewayInterface::class, $mock)` / `$this->app->instance(FeedbackInsightRepositoryInterface::class, $mock)` (см. `ContactAiExtractionTest.php`).
* Негативные кейсы («не отправлено», «не записано») так же важны, как позитивные.

## 5. Ручная диагностика реального AI-эндпоинта (вне автотестов)

Юнит/Feature-тесты (§2, §3) всегда мокают `AiGatewayInterface`/`Http` — они проверяют, что **наш код** правильно формирует запрос и обрабатывает ответ, но не проверяют, что **реальный** шлюз (`AI_GATEWAY_URL`) отвечает в ожидаемом формате и что промпт реально извлекает разумные данные.

Для этого — команда **`php artisan app:test-ai-extraction`** (`app/Console/Commands/TestAiExtraction.php`). Не часть автотестов (не запускается PHPUnit'ом, не входит в CI), только ручной запуск при необходимости проверить интеграцию с настоящим AI-провайдером.

* Прогоняет реальные примеры обращений из `directives/AI/Письма клиентов (Support_Mock_Data).xlsx` через настоящий `AiProcessingService` → настоящий `AiGatewayInterface`-биндинг (`OpenAiGateway`) → реальный `AI_GATEWAY_URL`.
* **Жёсткий лимит — не больше 3 примеров за запуск.** В коде это два независимых ограничителя: (а) в классе зашито ровно 3 примера (`SAMPLE_TEXTS`), (б) опция `--limit` дополнительно обрезается через `min($limit, 3)` — то есть даже `--limit=999` не даст прогнать больше трёх. Причина — не расходовать впустую квоту/лимиты реального AI-провайдера при каждой ручной проверке.
* Каждому тестовому обращению присваивается `id` вида `TESTRUN-N`, чтобы такие записи были легко отличимы от настоящих в `storage/logs/feedback-insight.log`.
* Использование: `php artisan app:test-ai-extraction` (все 3 примера) или `php artisan app:test-ai-extraction --limit=1` (только первый).
* Результат смотреть в `storage/logs/feedback-insight.log` (payload от AI) и `storage/logs/laravel.log` (если сработал fallback — см. `SPECS-AI.md`).
* Требует доступности `AI_GATEWAY_URL` из текущего окружения — если шлюз недоступен, все три вызова уйдут в fallback (ничего не запишется в `feedback-insight.log`, ошибки — в `laravel.log`).
