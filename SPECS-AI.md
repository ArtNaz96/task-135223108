# Спецификация: AI-интеграция

Вынесено из `ARCHITECTURE.md` §2.2 в отдельный файл.

## Общее
* AI-функция: анализ/извлечение структурированных данных из текста обращения (`$comment`) через внешний AI-провайдер.
* Согласно ТЗ провайдер может быть OpenAI, Anthropic или другой — поэтому протокол общения с провайдером закладывается **swappable** (через интерфейс), хотя реально используется всегда только один провайдер за раз (не строим мультипровайдерную систему, только оставляем точку замены).
* Реализовано и проверено на реальном AI-провайдере (см. раздел «Код» ниже).

## Юниты
* **`AiHandler`** (в коде — `App\Services\AiProcessingService`, переименован из `AiAnalysisService`): orchestrator — собирает запрос, вызывает `AiGatewayInterface`, реализует graceful fallback (если шлюз недоступен/вернул ошибку/невалидный JSON/таймаут — анализ пропускается, ошибка логируется, а не пробрасывается наверх).
* **`App\Services\AI\AiGatewayInterface`** — контракт вызова AI-провайдера. Позволяет заменить протокол (OpenAI ⇄ Anthropic ⇄ другой) без изменений в `AiProcessingService`.
* **`App\Services\AI\OpenAiGateway`** (текущая/единственная реализация интерфейса) — HTTP-клиент к OpenAI-совместимому Chat Completions API.
* **`App\DTO\AiRequestDTO`** / **`App\DTO\AiResponseDTO`** — контейнеры данных между `AiProcessingService` и `AiGatewayInterface`.
* **`App\Models\FeedbackInsight`** — POPO, результат AI-извлечения по конкретному обращению (см. «Судьба извлечённого JSON» ниже).
* **`FeedbackInsightRepositoryInterface`** + **`LogFeedbackInsightRepository`** — симметрично `FeedbackRepositoryInterface`/`FeedbackRepository` (тот же паттерн v1 (лог) → v2 (БД)).

## Системный промпт
* Хранится как статический файл в проекте: `resources/ai-prompts/feedback-extraction.txt`.
* Загружается и передаётся как есть, в качестве `system`-сообщения — никакой генерации/модификации промпта "на лету".
* текст на обработку, естественно, передаётся в AI-шлюз

## Контракт вызова (`OpenAiGateway`)
`POST {AI_GATEWAY_URL}/chat/completions` (переменная `AI_GATEWAY_URL` уже содержит `.../openai/v1` целиком, `/chat/completions` дописывается поверх неё).

Заголовки: `Authorization: Bearer {AI_GATEWAY_API_KEY}`.

Тело запроса:
```json
{
  "model": "{AI_GATEWAY_MODEL}",
  "temperature": 0,
  "response_format": {"type": "json_object"},
  "messages": [
    {"role": "system", "content": "<содержимое resources/ai-prompts/feedback-extraction.txt>"},
    {"role": "user", "content": "<$comment>"}
  ]
}
```

Ответ: `choices[0].message.content` парсится как JSON → `AiResponseDTO`.

## Переменные окружения
* `AI_GATEWAY_URL` — уже в спеках, без изменений (`http://127.0.0.1:8101/openai/v1`).
* `AI_GATEWAY_TIMEOUT` — уже в спеках, без изменений.
* **`AI_GATEWAY_MODEL`** (новая) — модель, конфигурируется, по умолчанию `gemini-advanced`. Инжектится в `OpenAiGateway` через конструктор (из `config('services.ai_gateway.model')` → `env('AI_GATEWAY_MODEL')`) — смена модели = правка `.env`, без изменения кода.
* **`AI_GATEWAY_API_KEY`** (новая) — ключ авторизации у провайдера, без значения по умолчанию (секрет).

## Извлекаемые данные
* Ориентировочная структура (не жёсткий контракт на данном этапе, строго не валидируется) — см. `directives/AI/entities.md`: `customer`/`products`/`intent` и т.п. Схема общая для e-commerce, без привязки к конкретной товарной нише (см. `directives/AI/A-4.md`, `directives/AI/A-5.md`).

* **Судьба извлечённого JSON:**
  * Порядок операций важен: `Feedback` сохраняется в `feedback_storage` **сразу** после валидации/санитизации — **до** вызова AI. Результат AI-извлечения физически не может попасть в ту же запись задним числом, поэтому нужна отдельная сущность.
  * **`App\Models\FeedbackInsight`** — POPO с полями `id` (то же значение, что у `Feedback::$id` — связывает две записи как внешний ключ) и `payload`.
  * Перед сохранением `payload` очищается от пустых значений — **`App\Support\ArrayPruner::pruneEmpty()`**: рекурсивно убирает `null`/`''`/`[]`, включая ветки, полностью опустевшие после очистки (например, `customer` со всеми `null`-полями пропадает целиком). `false`/`0` не считаются пустыми и остаются. Причина: сырой ответ AI по схеме `directives/AI/entities.md` почти всегда содержит много незаполненных полей (текст обращения редко упоминает все поля схемы разом) — без очистки лог `feedback_insight_storage` быстро раздувается пустыми JSON-каркасами без полезной информации.
  * **`FeedbackInsightRepositoryInterface`** + **`LogFeedbackInsightRepository`** сохраняют `FeedbackInsight` в отдельный псевдо-БД канал **`feedback_insight_storage`** (`Monolog`, файл `storage/logs/feedback-insight.log`) — симметрично `FeedbackRepository`/`feedback_storage`, тот же паттерн v1 (лог) → v2 (БД).
  * Запись создаётся **только при успешном** извлечении. При fallback (AI недоступен/ошибка/невалидный JSON/таймаут) в `feedback_insight_storage` **ничего не пишется** — неудачная попытка видна только в общем логе ошибок (`storage/logs/laravel.log`).
  * В письмо владельцу (`SPECS-Mail.md`) не добавляется — чтобы не завязывать точный текст письма на ориентировочную/нежёсткую AI-схему. Понадобится позже (например, в `GET /api/metrics` или админке) — решается отдельно.


## Fallback
Недоступность AI Gateway, ошибка ответа, невалидный JSON или истёкший таймаут → извлечение пропускается целиком, ошибка логируется (не пробрасывается наверх), основной сценарий (сохранение обращения, отправка письма) продолжается без AI-данных. Проверено вживую (не только в моках): реальный таймаут шлюза на одном из тестовых запросов сработал ровно так, как здесь описано.

## Переименование
`AiAnalysisService` → `AiProcessingService` — **применено** в коде (файл `app/Services/AiAnalysisService.php` удалён).

## Код
Реализовано по этой схеме: `AiRequestDTO`/`AiResponseDTO`, `AiGatewayInterface`/`OpenAiGateway` (OpenAI Chat Completions), `AiProcessingService` (оркестрация + graceful fallback), `FeedbackInsight`/`FeedbackInsightRepositoryInterface`/`LogFeedbackInsightRepository`, промпт-файл `resources/ai-prompts/feedback-extraction.txt`, конфигурация (`config/services.php`, `config/logging.php`), DI-биндинги (`AppServiceProvider`, `RepositoryServiceProvider`). `FeedbackService` (тогда ещё `FeedbackProcessingService`, переименован позже вместе с Mail-слоем)/`Feedback` (модель) дополнены полем `id` и вызовом `AiProcessingService` — это уже относится к Feedback-слою, не к Mail, поэтому не гейтилось утверждением `SPECS-Mail.md`.

## Тесты
См. `TESTING.md` §3.6–3.8 — `AiProcessingServiceTest`, `OpenAiGatewayTest`, `LogFeedbackInsightRepositoryTest`.
