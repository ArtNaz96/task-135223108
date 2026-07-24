# Portfolio Backend API & AI Service

Backend-сервис для лендинг-презентации разработчика с полноценным REST API, файловой системой хранения данных, асинхронными email-уведомлениями и AI-интеграцией (извлечение инсайтов с поддержкой Graceful Fallback).

---

## 1. Как запустить проект

### Требования к окружению
* **PHP:** >= 8.2 (расширения: `curl`, `mbstring`, `json`, `openssl`, `fileinfo`)
* **Composer:** >= 2.0

### Пошаговая установка

1. **Клонирование репозитория и переход в директорию:**
   ```bash
   git clone git@github.com:ArtNaz96/task-135223108.git
   cd task-135223108
   ```

2. **Установка зависимостей:**
   ```bash
   composer install
   ```

3. **Настройка переменных окружения:**
   Скопируйте пример файла конфигурации `.env.example` в `.env`:
   ```bash
   cp .env.example .env
   ```
   Сгенерируйте ключ приложения:
   ```bash
   php artisan key:generate
   ```

4. **Конфигурация `.env`:**
   Отредактируйте переменные в `.env` при необходимости:
   ```env
AI_GATEWAY_URL=http://127.0.0.1:8101/openai/v1
AI_GATEWAY_TIMEOUT=10
AI_GATEWAY_MODEL=gemini-advanced
AI_GATEWAY_API_KEY=
SITE_OWNER_EMAIL=owner@example.com
FEEDBACK_RATE_LIMIT_PER_MINUTE=5
FEEDBACK_NAME_MAX_LENGTH=255
FEEDBACK_COMMENT_MAX_LENGTH=2000
API_ACCESS_TOKEN=<token>
CORS_ALLOWED_ORIGINS=*
   ```

5. **Запуск локального сервера:**
   ```bash
   php artisan serve --port=8000
   ```
   Сервис будет доступен по адресу: `http://127.0.0.1:8000`.

6. **Запуск автоматических тестов:**
   ```bash
   php artisan test
   ```
7. **Дополнительно** 
   Проект также доступен в интернет по адресу: http://31.77.169.148:8000.
   Нужно будет передать заголовок:
   "Authorization: Bearer <token>"

---

## 2. Стек технологий

* **Backend Framework:** [Laravel 13](https://laravel.com/) (PHP 8.2+) — легковесная REST-ориентированная архитектура.
* **Логирование и Хранение:** [Monolog](https://github.com/Seldaek/monolog) — кастомные файловые каналы в `storage/logs/`.
* **AI Provider:** OpenAI API / AI Gateway (совместимый Chat Completions API) — модель `gemini-advanced`.
* **Тестирование:** PHPUnit (Unit & Feature тесты).
* **Уведомления:** Laravel Mailables / Queue System (в режиме `sync` для локального окружения).

---

## 3. Архитектура

Приложение спроектировано по принципам **Layered Architecture (Слоистая архитектура)** без использования традиционной СУБД, удовлетворяя требованиям к высокоскоростной работе с файловым хранилищем:

```
[ HTTP Request ] 
       │
       ▼
[ Controller ] (FeedbackController)
       │
       ▼
[ Form Request ] (FeedbackRequest — Валидация)
       │
       ▼
[ Service Layer ] (FeedbackService — Бизнес-логика, Sanitizer)
    ├──► [ Repository ] ──► File Storage (feedback.log)
    ├──► [ AI Service ]  ──► OpenAI Gateway ──► File Storage (feedback-insight.log)
    └──► [ Mailer Job ]  ──► Mail Storage (mail.log / SMTP)
```

### Паттерны проектирования и ключевые решения
1. **Repository Pattern:** Абстрагирование слоя хранения через `FeedbackRepositoryInterface` и `FeedbackInsightRepositoryInterface`. Реализации `LogFeedbackRepository` и `LogFeedbackInsightRepository` пишут данные в файловую систему в формате JSON/JSON Lines.
2. **DTO (Data Transfer Object):** Использование недоменных объектов (`App\DTO\Feedback`, `App\Models\FeedbackInsight`) для строгой типизации данных между слоями приложения.
3. **Graceful Fallback:** AI-сервис обёрнут в изоляционный блок. В случае недоступности AI, превышения таймаута или ошибки форматирования JSON, ошибка фиксируется в логах, а основной процесс приема обратной связи завершается успешно (HTTP 201).
4. **Sanitization:** Сервис `InputSanitizer` гарантирует очистку полей от вредоносных HTML-тегов и управляющих символов с сохранением форматирования переносов строк.

---

## 4. Реализация API

### Таблица Эндпоинтов

| Метод | Эндпоинт | Описание | Успешный статус |
|---|---|---|---|
| `GET` | `/api/v1/health` | Проверка работоспособности сервиса | `200 OK` |
| `POST` | `/api/v1/contact` | Отправка формы обратной связи | `201 Created` |
| `GET` | `/api/v1/metrics` | Статистика обращений (заглушка) | `501 Not Implemented` |

### Интерактивная OpenAPI / Swagger документация (Redoc UI)
Интерактивная HTML-документация с детальным описанием схем запросов и ответов доступна по адресу:
👉 **[http://127.0.0.1:8000/docs.html](http://127.0.0.1:8000/docs.html)** (спецификация в YAML: `/openapi.yaml`)

### Валидация и коды ошибок
* **`401 Unauthorized`:** Отсутствующий или неверный токен авторизации (`Authorization: Bearer`).
* **`422 Unprocessable Entity`:** Ошибка валидации входных данных (отсутствие обязательных полей, невалидный email/phone).
* **`429 Too Many Requests`:** Превышение лимита отправки сообщений (более 5 запросов в минуту с одного IP).
* **`500 Internal Server Error`:** Непредвиденная критическая ошибка сервера.

---

### Примеры cURL-запросов

#### 1. Проверка состояния сервиса (`GET /api/v1/health`)
```bash
curl -i -X GET http://127.0.0.1:8000/api/v1/health \
  -H "Accept: application/json"
```
**Ответ `200 OK`:**
```json
{
  "status": "ok"
}
```

#### 2. Успешная отправка формы (`POST /api/v1/contact`)
```bash
curl -i -X POST http://127.0.0.1:8000/api/v1/contact \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Алексей Смирнов",
    "phone": "+79991234567",
    "email": "alexey@example.com",
    "comment": "Здравствуйте! Интересует стоимость и сроки разработки REST API на Laravel."
  }'
```
**Ответ `201 Created`:**
```json
{
  "message": "Feedback successfully received",
  "data": {
    "id": "20260724120000-123456",
    "name": "Алексей Смирнов",
    "phone": "+79991234567",
    "email": "alexey@example.com",
    "comment": "Здравствуйте! Интересует стоимость и сроки разработки REST API на Laravel."
  }
}
```

#### 3. Ошибка валидации (`POST /api/v1/contact`)
```bash
curl -i -X POST http://127.0.0.1:8000/api/v1/contact \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "phone": "+79991234567",
    "email": "alexey@example.com",
    "comment": "Тест без имени"
  }'
```
**Ответ `422 Unprocessable Entity`:**
```json
{
  "message": "The name field is required.",
  "errors": {
    "name": [
      "The name field is required."
    ]
  }
}
```

#### 4. Rate Limiting (`429 Too Many Requests`)
При превышении порога в 5 запросов/мин:
**Ответ `429 Too Many Requests`:**
```json
{
  "message": "Too Many Requests"
}
```

---

## 5. AI-интеграция

### Назначение и функционал
AI-компонент на базе OpenAI Chat Completions API выполняет интеллектуальное извлечение структурированных данных (Structured Insights Extraction) из полученного от пользователя комментария.

### Извлекаемые сущности:
Согласно системному промпту (`resources/ai-prompts/feedback-extraction.txt`), AI возвращает JSON с тремя блоками:
1. **`customer`** — `order_id`, `phone_number`, `email`, `delivery_address`, `delivery_date`.
2. **`products`** (массив, по одной записи на каждый упомянутый товар) — `brand`, `product_type`, `variant`, `size_weight`, `quantity`.
3. **`intent`** — `intent_category` (`delay` / `return` / `consultation` / `defect` / `other`), `urgency` (`true`/`false`), `problem_description`.

Незаполненные поля приходят как `null`/`[]` и перед сохранением очищаются (`App\Support\ArrayPruner::pruneEmpty()`), чтобы не раздувать лог пустыми JSON-каркасами без полезной информации.

### Механизм Graceful Fallback
1. Вызов AI выполняется в асинхронном/изолированном стиле внутри `AiProcessingService`.
2. Если AI-сервис недоступен, превышен таймаут (`AI_GATEWAY_TIMEOUT=10`) или получен некорректный JSON, ошибка записывается в `storage/logs/laravel.log`.
3. Заявка пользователя гарантированно сохраняется в `storage/logs/feedback.log`, а пользователь получает подтверждение отправки.

---

## 6. Что сделано с помощью AI

### Генерируемый код
* Практически весь.
### Какие промпты использовали
* Я их давал много. Примеры промптов находятся в папке `/prompt.examples`.
### Что пришлось исправлять вручную
* Сгенерённые AI-ем спеки, для большей точности.

---

## 7. Хранение данных и инфраструктура

### 1. Файловое хранилище (Monolog Logging Drivers)
Хранение обращений организовано без использования SQL-базы данных на базе изолированных каналов Monolog (`config/logging.php`):
* **Обращения клиентов:** Записываются в `storage/logs/feedback.log` в формате JSON.
* **ИИ-Инсайты:** Записываются в `storage/logs/feedback-insight.log`.
* **Email-логи:** В режиме разработки сохраняются в `storage/logs/mail.log`.

### 2. Rate Limiting
Ограничение частоты запросов реализовано через встроенный фасады `RateLimiter::for('contact-form')` в `AppServiceProvider`.
* Порог ограничений настраивается через переменную `.env`: `FEEDBACK_RATE_LIMIT_PER_MINUTE=5`.
* Учёт ведётся по IP-адресу клиентов с использованием файлового кэша Laravel.

### 3. Метрики и статистика
* Эндпоинт `GET /api/v1/metrics` зарезервирован для вывода агрегированной статистики по сохраненным в файлах логам (возвращает `501 Not Implemented`).
