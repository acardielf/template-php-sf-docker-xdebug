# CLAUDE.md — Project Guidelines

This file defines the conventions, architecture, and toolchain for this project.
**Read it fully before making any change.**

---

## 1. Stack

| Layer                                 | Technology                                       |
|---------------------------------------|--------------------------------------------------|
| Language                              | PHP 8.5 (strict_types, strong typing everywhere) |
| Framework                             | Symfony 7.3                                      |
| ORM                                   | Doctrine ORM 3.x                                 |
| Queue                                 | Symfony Messenger + Redis                        |
| Mailer                                | Symfony Mailer + Mailpit (dev)                   |
| Frontend                              | Twig + Tailwind CSS v4 (via AssetMapper)         |
| API Docs                              | NelmioApiDocBundle (OpenAPI / Swagger)           |
| Database                              | PostgreSQL 16 (all environments — no SQLite)     |
| Containerization                      | Docker + Docker Compose                          |
| Reverse proxy                         | Nginx                                            |
| Static analysis                       | PHPStan level 9                                  |
| Code style                            | PHP CS Fixer (PSR-12 + Symfony rules)            |
| Refactoring                           | Rector (auto-upgrade + code quality)             |
| Testing (unit/integration/functional) | PHPUnit 11.5                                     |
| Testing (BDD)                         | Behat 3.x                                        |
| Code quality                          | SonarQube (community, dockerized)                |
| CI/CD                                 | GitHub Actions                                   |

---

## 2. Architecture — Hexagonal (Ports & Adapters)

All code lives under `src/` organized into three layers:

```
src/
├── Application/          # Use cases — Commands, Queries, Handlers
│   ├── Command/          # Intent to change state (CQRS Write side)
│   ├── Query/            # Request for data   (CQRS Read side)
│   └── Service/          # Ports: CommandBusInterface, QueryBusInterface
│
├── Domain/               # Pure business logic — NO framework dependencies
│   ├── Entity/           # Aggregate roots and entities
│   ├── Event/            # Domain events
│   ├── Exception/        # Domain-specific exceptions (extend DomainException)
│   ├── Repository/       # Repository interfaces (ports)
│   └── ValueObject/      # Immutable value objects
│
└── Infrastructure/       # Adapters — framework, DB, HTTP, external services
    ├── Controller/       # Symfony controllers (thin — delegate to buses)
    ├── EventSubscriber/  # ExceptionSubscriber — maps exceptions to HTTP responses
    ├── Exception/        # Infrastructure exceptions (PersistenceException, MailerException…)
    ├── Http/Request/     # Typed, validated request objects
    ├── Messenger/
    │   ├── Message/      # Async Messenger messages (SendEmailMessage…)
    │   └── Handler/      # Async message handlers (SendEmailMessageHandler…)
    ├── Persistence/      # Doctrine entities, repositories, migrations
    └── Repository/       # Doctrine implementations of domain repository ports
```

### Rules

- **Domain layer has zero dependencies on Symfony, Doctrine, or any vendor package.**
- Controllers are thin: validate input → dispatch command/query → return response.
- All business logic lives in Domain entities or Domain Services.
- Infrastructure adapters implement Domain ports (interfaces).
- Repositories return **Domain** entities/value objects, never Doctrine entities directly (use mappers).

---

## 3. Code Standards

### Language

- All code, variable names, method names, class names, comments, and docblocks **must be in English**.
- Exception: translation keys in `translations/` files follow dot notation and are also in English as keys.

### Typing

- Every file **must** start with `declare(strict_types=1);`.
- All method parameters and return types **must** be explicitly typed — no `mixed` unless genuinely unavoidable.
- PHPDoc is mandatory for:
  - Arrays: always annotate the shape, e.g. `@param array<string, mixed> $data` or `@return list<UserDto>`.
  - Generics and templates: `@template T`, `@param Collection<int, T>`.
  - Cases where PHP's type system is not expressive enough.
- Use `readonly` properties and constructor promotion where possible.
- Prefer `enum` over class constants for finite sets of values.

### Naming

- Classes: `PascalCase`
- Methods/functions: `camelCase`
- Variables: `camelCase`
- Constants/enum cases: `SCREAMING_SNAKE_CASE` / `PascalCase` (for backed enums)
- Files: same as class name, one class per file.

### SOLID Principles

- **S** — One reason to change per class. Controllers only coordinate; domain does logic.
- **O** — Extend via new classes; do not modify existing stable ones for new features.
- **L** — Subtypes honour contracts of their parents.
- **I** — Small, focused interfaces. Avoid fat interfaces.
- **D** — Depend on abstractions (ports), never on concrete infrastructure classes from the domain.

### Comments

- Every `public` method **must** have a PHPDoc block explaining what it does, its parameters, and its return value.
- Use `/** @throws ExceptionClass */` for all thrown exceptions.
- Do not comment what the code already says clearly. Explain **why**, not **what**.

---

## 4. API Controllers

- Every controller action **must** be annotated with OpenAPI attributes from `OpenApi\Attributes`.
- Tag each controller class with `#[OA\Tag(name: 'ResourceName')]`.
- Document all possible HTTP responses including 4xx and 5xx.
- Use `AbstractApiController` as the base class.
- Use `AbstractApiRequest` for typed, validated request bodies.

Example:

```php
#[Route('/api/users', name: 'api_user_create', methods: ['POST'])]
#[OA\Post(
    path: '/api/users',
    summary: 'Create a new user',
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreateUserRequest')),
    responses: [
        new OA\Response(response: 201, description: 'User created'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
public function create(CreateUserRequest $request): JsonResponse { ... }
```

---

## 5. Testing

### PHPUnit

- **Unit tests** (`tests/Unit/`) — test domain classes in isolation; no Symfony, no DB.
- **Integration tests** (`tests/Integration/`) — test infrastructure adapters against the `app_test` PostgreSQL database.
- **Functional tests** (`tests/Functional/`) — test HTTP controllers via `WebTestCase`.
- All tests use `#[Test]`, `#[CoversClass]`, `#[DataProvider]` attributes (PHPUnit 11 style).
- Target: **≥ 80% coverage**. Coverage enforced in CI.

### Behat

- BDD scenarios live in `features/`.
- Feature files use the Given/When/Then format in plain English.
- Context classes live in `features/contexts/` and `features/bootstrap/`.

### Running tests

```bash
# All tests
composer test

# With coverage
composer test:coverage

# Behat only
composer test:behat

# PHPStan
composer phpstan

# PHP CS Fixer (check only)
composer cs:check

# PHP CS Fixer (fix)
composer cs:fix

# Rector (check)
composer rector:check

# Rector (fix)
composer rector:fix

# All quality checks
composer quality
```

---

## 6. Docker Environments

**SQLite is not used anywhere.** PostgreSQL runs in every environment, including local development and CI.
The `app_test` database (created automatically by `.docker/postgres/init.sql`) is used by PHPUnit and Behat.

### Standard (PostgreSQL + Redis + Mailpit)

```bash
bash bin/init.sh        # copies .env, generates APP_SECRET, starts containers, runs migrations
```

Or manually:

```bash
cp .env.example .env
docker compose up -d
docker compose exec php composer install
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

### With SonarQube

```bash
bash bin/init.sh dev
# Or manually:
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

### Services available

| Service    | URL / Port                         |
|------------|------------------------------------|
| Application | http://localhost:8080              |
| API Docs   | http://localhost:8080/api/doc      |
| Mailpit    | http://localhost:8025              |
| SonarQube  | http://localhost:9000              |
| Redis      | localhost:6379                     |
| PostgreSQL | localhost:5432                     |

---

## 7. Exception Hierarchy

Three distinct exception bases — one per layer. **Never let an inner layer's exception type leak to an outer layer.**

```
Domain\Exception\DomainException (abstract)
├── EntityNotFoundException          → HTTP 404
├── DuplicateEntityException         → HTTP 409
├── BusinessRuleViolationException   → HTTP 422
└── InvalidValueObjectException      → HTTP 422

Application\Exception\ApplicationException (abstract)
├── UnauthorizedException            → HTTP 403
└── ValidationException              → HTTP 422  (carries violations map)

Infrastructure\Exception\InfrastructureException (abstract)
├── PersistenceException             → HTTP 500
├── MessagingException               → HTTP 500
├── MailerException                  → HTTP 500
└── ExternalServiceException         → HTTP 500 / 502
```

**`ExceptionSubscriber`** (`src/Infrastructure/EventSubscriber/ExceptionSubscriber.php`) is the single translation point. It catches every exception type and converts it to the correct JSON HTTP response. It also logs:

- `InfrastructureException` and unknown exceptions → `logger.error` (full trace)
- Domain and Application exceptions → `logger.warning` (expected failures)

### Rules

- Domain exceptions: throw inside entities, value objects, and domain services.
- Application exceptions: throw inside command/query handlers when orchestration fails.
- Infrastructure exceptions: throw inside repositories, message handlers, mailer adapters.
- **Never throw `InfrastructureException` from domain or application classes.**
- **Never expose raw infrastructure exception messages to the client** — they may contain sensitive query details.

### Named factory methods

All exceptions use static factory methods for readability and to capture structured context:

```php
// Good
throw EntityNotFoundException::forEntity(User::class, $id);
throw BusinessRuleViolationException::forRule('order.cannot_cancel_shipped', 'Order is already shipped.');
throw PersistenceException::forOperation('save', Order::class, $doctrineException);

// Bad — loses context
throw new DomainException('Not found');
```

---

## 8. Async Queue — Email and Notifications

### Transports

| Transport           | Stream                  | Use for                                |
|---------------------|-------------------------|----------------------------------------|
| `async_high_priority` | `messages_high_priority` | Emails, push notifications, urgent tasks |
| `async`             | `messages_async`         | Reports, exports, non-urgent background work |
| `failed`            | Doctrine table           | Dead-letter queue — inspect & retry manually |

### Worker

The `messenger-worker` container consumes both queues:

```
php bin/console messenger:consume async async_high_priority --time-limit=3600 --memory-limit=128M
```

### Adding a new async task

1. Create a `Message` class in `src/Infrastructure/Messenger/Message/`.
2. Create a `Handler` class in `src/Infrastructure/Messenger/Handler/`, tagged with `#[AsMessageHandler(fromTransport: 'async_high_priority')]` or `async`.
3. Register the routing in `config/packages/messenger.yaml`.

### Email pattern

```
Controller / CLI
    └─▶ CommandBus.dispatch(SendWelcomeEmailCommand)
            └─▶ SendWelcomeEmailHandler          [Application layer]
                    └─▶ asyncBus.dispatch(SendEmailMessage)   [async_high_priority]
                            └─▶ [Worker picks up] SendEmailMessageHandler  [Infrastructure]
                                    └─▶ Symfony Mailer → Mailpit / SMTP
```

The application handler never waits for the email to be sent. If the broker is unreachable, a `MessagingException` is thrown immediately. If the mailer fails after delivery to the broker, Messenger retries up to 5 times with exponential backoff; on permanent failure the message lands in the `failed` transport.

---

## 9. Branching Model (GitHub Flow + Release branches)

```
main            ← production-ready, protected, requires PR + CI green
develop         ← integration branch, protected
feature/xxx     ← new features, branched from develop
bugfix/xxx      ← non-critical bug fixes, branched from develop
hotfix/xxx      ← critical production fixes, branched from main
release/x.x.x  ← release preparation, branched from develop
```

### Rules

- Never commit directly to `main` or `develop`.
- All changes enter via Pull Request with at least one approval.
- PRs must pass CI (quality checks + tests + SonarQube gate) before merge.
- Commits follow [Conventional Commits](https://www.conventionalcommits.org/):
  `feat:`, `fix:`, `chore:`, `docs:`, `test:`, `refactor:`, `ci:`.

---

## 10. Pre-commit Hooks

Installed via `bash bin/setup-hooks.sh` (run once after cloning).

The hook runs in order:
1. PHP CS Fixer (dry-run) — fails if formatting is wrong.
2. PHPStan — fails if static analysis errors exist.
3. Rector (dry-run) — fails if refactoring suggestions exist.
4. PHPUnit (no coverage) — fails if any test fails.
5. SonarQube (background, only if `SONAR_TOKEN` is set).

Auto-fix before committing:

```bash
composer quality:fix   # runs cs:fix + rector:fix
```

---

## 11. SonarQube Setup (first time)

1. Start SonarQube: `docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d sonarqube`
2. Open http://localhost:9000 and log in with `admin`/`admin`.
3. Create a project manually with the key `php-sf-template`.
4. Generate a project token and add it to `.env` as `SONAR_TOKEN`.
5. SonarQube will receive analysis results:
   - From the pre-commit hook (local).
   - From GitHub Actions `sonarqube` job on every push.

---

## 12. Translations (i18n)

- All user-facing strings must use `$translator->trans('key')` or `'key'|trans` in Twig.
- Translation files live in `translations/messages.<locale>.yaml`.
- Default locale: `en`. Fallback: `en`.
- Add new languages by creating `translations/messages.<locale>.yaml`.
- Keys use dot notation: `user.created`, `error.not_found`, etc.

---

## 13. Directory Quick Reference

```
.docker/         Docker build context and service configs
.github/         GitHub Actions workflows
assets/          Frontend JS and CSS (AssetMapper)
bin/             Symfony console + utility scripts
config/          Symfony configuration (packages, routes, services)
features/        Behat scenarios and contexts
public/          Web root (index.php only)
src/
  Application/   Commands, Queries, Buses (ports)
  Domain/        Entities, Value Objects, Events, Exceptions, Repository interfaces
  Infrastructure/ Controllers, Adapters, Doctrine, Messenger implementations
templates/       Twig templates
tests/           PHPUnit test suites (Unit, Integration, Functional)
translations/    i18n YAML files
```
