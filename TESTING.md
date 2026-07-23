# Спецификация тестов

Дополняет `PRODUCT.md` (AC 6.2 — «написанные юнит-тесты успешно проходят без ошибок» не говорит, что именно нужно покрыть). Здесь — конкретно что, на каком уровне и как.

## 1. Уровни тестирования

* **Feature-тесты** (`tests/Feature/...`) — через реальный HTTP-слой (`Route`/`Controller`), с `RefreshDatabase` при необходимости (очередь на `database`-драйвере). Проверяют контракт эндпоинта: статус, тело ответа, побочные эффекты (лог, письмо).
* **Unit-тесты** (`tests/Unit/...`) — изолированно, без HTTP: сервисы, репозиторий, Mailable, генерация `id`.

## 2. Feature-тесты

### 2.1. `tests/Feature/Api/V1/ContactControllerTest.php`
Покрывает `POST /api/v1/contact`.

| Тест | Сценарий | AC |
|---|---|---|
| `it_accepts_valid_payload` | Валидные `name`/`phone`/`email`/`comment` → 201, тело содержит `message` и `data` | AC 1.1, AC 1.2 |
| `it_rejects_missing_required_field` | По очереди отсутствует каждое обязательное поле → 422, JSON с описанием ошибки | AC 2.1, §7 |
| `it_rejects_invalid_email_format` | `email` некорректного формата → 422 | AC 2.2, §7 |
| `it_rejects_comment_longer_than_2000_chars` | `comment` > 2000 символов («простыня») → 422 | AC 2.3, §7 |
| `it_returns_429_when_rate_limit_exceeded` | N+1-й запрос с одного IP в пределах окна лимита → 429 | AC 2.4, §7 |
| `it_returns_500_without_leaking_internals_on_unexpected_error` | Принудительно смоделированное исключение (например, через мок сервиса, который бросает `\RuntimeException`) → 500, JSON без стектрейса; проверить запись в `storage/logs/laravel.log` | AC 2.5, §7 |
| `it_processes_comment_through_ai_service` | В сохранённых/возвращённых данных присутствует признак обработки AI (`AI DONE` либо мок сервиса, проверка вызова) | AC 3.1 |
| `it_falls_back_gracefully_when_ai_service_fails` | AI-сервис/шлюз бросает исключение или недоступен → запрос всё равно завершается 201, ошибка не пробрасывается | AC 3.2 |
| `it_sends_one_mail_to_owner_with_user_cc` | `Mail::fake()` → после валидного запроса отправлено ровно одно письмо, `to` = `SITE_OWNER_EMAIL`, `cc` содержит `$feedback->email` | AC 4.1, AC 4.2 |
| `it_does_not_send_mail_on_validation_failure` | Невалидный запрос → писем не отправлено | AC 4.1/4.2 (негативный кейс) |
| `it_writes_valid_feedback_to_feedback_storage_channel` | Валидный запрос → в канал `feedback_storage` записана строка со всеми атрибутами обращения | AC 5.1 |
| `it_does_not_write_invalid_requests_to_feedback_storage_channel` | Невалидный запрос → канал `feedback_storage` не тронут | AC 5.1 (негативный кейс) |

### 2.2. `tests/Feature/Api/V1/HealthControllerTest.php`
| Тест | Сценарий |
|---|---|
| `it_returns_ok_status` | `GET /api/v1/health` → 200, JSON `{"status": "ok"}` |

### 2.3. `tests/Feature/Api/V1/MetricsControllerTest.php`
| Тест | Сценарий |
|---|---|
| `it_returns_not_implemented` | `GET /api/v1/metrics` → `501 Not Implemented` (маршрут и контроллер существуют, реальная логика — предмет будущей доработки, см. `ARCHITECTURE.md` §4) |

## 3. Unit-тесты

### 3.1. `tests/Unit/Services/FeedbackProcessingServiceTest.php`
* `it_builds_feedback_dto_from_input_array` — из массива данных собирается корректный `Feedback`.
* `it_delegates_comment_to_ai_service` — AI-сервис вызывается с исходным текстом комментария (мок).
* `it_saves_feedback_via_repository` — репозиторий вызывается ровно один раз с собранным `Feedback` (мок `FeedbackRepositoryInterface`).
* `it_generates_id_matching_expected_format` — `id` соответствует `^\d{14}-\d{6}$` (`YYYYMMDDHHMMSS-<6 цифр>`), см. `ARCHITECTURE.md` §2.3.

### 3.2. `tests/Unit/Services/AiAnalysisServiceTest.php` *(или `AiHandlerTest`/`AiGatewayTest`, когда появятся)*
* `it_prefixes_text_with_ai_done_marker` — заглушка возвращает `"AI DONE\n" . $text`.
* `it_does_not_mutate_original_comment_used_elsewhere` — убедиться, что обработанный AI текст не подменяет оригинал там, где требуется «ровно как пришло» (тело письма, §2.5 `ARCHITECTURE.md`).

### 3.3. `tests/Unit/Repositories/LogFeedbackRepositoryTest.php`
* `it_writes_to_feedback_storage_channel` — `save()` пишет именно в канал `feedback_storage`, не в `single`/`stack` по умолчанию.
* `it_includes_all_feedback_attributes_in_log_entry` — в записи присутствуют все поля `Feedback` (включая `id`).

### 3.4. `tests/Unit/Mail/OwnerFeedbackMailTest.php`
* `it_has_subject_with_request_id` — тема письма `Запрос #<id>`.
* `it_renders_body_with_exact_template` — тело: `Ваш запрос #<id> получен и принят в работу.` → пустая строка → `Сообщение:` → пустая строка → `$feedback->comment` без изменений → `<подпись>` (`MAIL_SIGNATURE`).
* `it_is_addressed_to_owner_and_cc_to_user` — `to()` = `SITE_OWNER_EMAIL`, `cc()` = `$feedback->email`.

## 4. Инструменты/подходы

* `Mail::fake()` + `Mail::assertSent(...)` — для писем, без реальной отправки.
* Для проверки записи в конкретный Monolog-канал — `Log::channel('feedback_storage')` со спаем/моком handler'а, либо (проще) отдельный тестовый канал, пишущий во временный файл, с последующей проверкой содержимого файла.
* `Queue::fake()` — там, где отправка писем идёт через `ShouldQueue` Job, чтобы не тестировать реальную очередь.
* Негативные кейсы («не отправлено», «не записано») так же важны, как позитивные — явно перечислены выше, чтобы не потерялись.
