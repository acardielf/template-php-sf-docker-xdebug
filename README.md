# PHP · Symfony · Docker Template

Production-ready Symfony 7.3 project template with hexagonal architecture, full Docker stack, and a complete quality toolchain out of the box.

---

## Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.5 · `strict_types` · strong typing everywhere |
| Framework | Symfony 7.3 |
| ORM | Doctrine ORM 3.x |
| Database | PostgreSQL 16 (all environments) |
| Queue | Symfony Messenger + Redis |
| Mailer | Symfony Mailer + Mailpit (local) |
| Frontend | Twig + Tailwind CSS v4 (AssetMapper) |
| API Docs | NelmioApiDocBundle — OpenAPI / Swagger UI |
| Reverse proxy | Nginx |
| Containerization | Docker + Docker Compose |
| Static analysis | PHPStan level 9 |
| Code style | PHP CS Fixer — PSR-12 + Symfony rules |
| Refactoring | Rector — auto-upgrade + code quality |
| Testing | PHPUnit 11.5 (unit · integration · functional) |
| BDD | Behat 3.x |
| Code quality | SonarQube Community (dockerized) |
| CI/CD | GitHub Actions |

---

## Architecture

Hexagonal (Ports & Adapters) with CQRS:

```
src/
├── Application/        Commands, Queries, Handlers, Bus ports
├── Domain/             Entities, Value Objects, Events, Exceptions, Repository interfaces
└── Infrastructure/     Controllers, Doctrine, Messenger, EventSubscriber, HTTP adapters
```

**Rules:** the Domain layer has zero dependencies on Symfony, Doctrine, or any vendor package. Controllers are thin — they validate input, dispatch a command/query, and return a response.

### Exception hierarchy

One base exception per layer so errors are always traceable to their origin:

```
DomainException (abstract)
├── EntityNotFoundException          → 404
├── DuplicateEntityException         → 409
├── BusinessRuleViolationException   → 422
└── InvalidValueObjectException      → 422

ApplicationException (abstract)
├── UnauthorizedException            → 403
└── ValidationException              → 422

InfrastructureException (abstract)
├── PersistenceException             → 500
├── MessagingException               → 500
├── MailerException                  → 500
└── ExternalServiceException         → 500
```

`ExceptionSubscriber` translates every exception to a consistent JSON response. Infrastructure messages are never exposed to the client.

### Async queue pattern (email example)

```
CommandBus.dispatch(SendWelcomeEmailCommand)
  └─▶ SendWelcomeEmailHandler       [Application]
        └─▶ asyncBus.dispatch(SendEmailMessage)   → async_high_priority transport
              └─▶ [Worker] SendEmailMessageHandler  [Infrastructure]
                    └─▶ Symfony Mailer → Mailpit / SMTP
```

Two transports: `async_high_priority` (emails, notifications) and `async` (reports, exports). Failed messages land in a dead-letter queue for manual retry.

---

## Requirements

- Docker + Docker Compose v2
- `openssl` (for secret generation in `bin/init.sh`)
- Node / npm (optional — only if you build Tailwind locally)

---

## Getting started

### 1. Use this template

Click **"Use this template"** on GitHub, or clone it directly:

```bash
git clone https://github.com/your-org/template-php-sf-docker.git my-project
cd my-project
```

### 2. Bootstrap

```bash
# Standard: PostgreSQL + Redis + Mailpit
bash bin/init.sh

# With SonarQube (code quality dashboard)
bash bin/init.sh dev
```

The script will:
1. Copy `.env.example` → `.env`
2. Generate a random `APP_SECRET`
3. Start all Docker services
4. Install Composer dependencies
5. Run database migrations
6. Install git pre-commit hooks

### 3. Open the app

| Service | URL |
|---|---|
| Application | http://localhost:8080 |
| Swagger UI | http://localhost:8080/api/doc |
| Mailpit | http://localhost:8025 |
| SonarQube | http://localhost:9000 |
| PostgreSQL | `localhost:5432` |
| Redis | `localhost:6379` |

---

## Manual setup (step by step)

```bash
cp .env.example .env
# Edit .env — set DATABASE_URL, APP_SECRET, etc.

docker compose up -d
docker compose exec php composer install
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

---

## Daily development

```bash
# Enter the PHP container
docker compose exec php bash

# Run migrations
docker compose exec php bin/console doctrine:migrations:migrate

# Generate a new migration after entity changes
docker compose exec php bin/console doctrine:migrations:diff

# Clear cache
docker compose exec php bin/console cache:clear

# Consume the async queues manually
docker compose exec php bin/console messenger:consume async async_high_priority

# Inspect failed messages
docker compose exec php bin/console messenger:failed:show
docker compose exec php bin/console messenger:failed:retry
```

---

## Quality checks

```bash
# All checks (CS + PHPStan + Rector)
composer quality

# Auto-fix formatting and refactoring
composer quality:fix

# Static analysis only
composer phpstan

# Code style check / fix
composer cs:check
composer cs:fix

# Rector check / fix
composer rector:check
composer rector:fix
```

---

## Testing

```bash
# All tests
composer test

# With coverage report
composer test:coverage

# BDD (Behat) only
composer test:behat

# Single test suite
php bin/phpunit --testsuite Unit
php bin/phpunit --testsuite Integration
php bin/phpunit --testsuite Functional
```

Tests run against a dedicated `app_test` PostgreSQL database (created automatically by `.docker/postgres/init.sql`).

---

## SonarQube setup (first time)

```bash
# Start with the dev profile
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d sonarqube
```

1. Open http://localhost:9000 — log in with `admin` / `admin`
2. Create a project with key `php-sf-template`
3. Generate a token and add it to `.env` as `SONAR_TOKEN`

Analysis runs automatically on every commit (pre-commit hook) and on every push (GitHub Actions).

---

## Adding a new feature

```
# 1. Add domain logic
src/Domain/Entity/Order.php              ← aggregate with business rules
src/Domain/Repository/OrderRepositoryInterface.php  ← port

# 2. Create use case
src/Application/Command/Order/CreateOrderCommand.php
src/Application/Command/Order/CreateOrderHandler.php

# 3. Wire the adapter
src/Infrastructure/Repository/DoctrineOrderRepository.php
src/Infrastructure/Persistence/Entity/OrderEntity.php

# 4. Expose via controller
src/Infrastructure/Controller/OrderController.php   ← thin, delegates to CommandBus

# 5. Document with OpenAPI attributes and add tests
```

---

## Branching model

```
main          ← production, protected — PR + CI required
develop       ← integration branch, protected
feature/xxx   ← branched from develop
bugfix/xxx    ← branched from develop
hotfix/xxx    ← branched from main
release/x.x.x ← branched from develop
```

Commits follow [Conventional Commits](https://www.conventionalcommits.org/): `feat:`, `fix:`, `chore:`, `docs:`, `test:`, `refactor:`, `ci:`.

---

## Pre-commit hooks

Installed automatically by `bin/init.sh`. Run manually with `bash bin/setup-hooks.sh`.

On every commit the hook verifies in order:
1. PHP CS Fixer — formatting
2. PHPStan — static analysis
3. Rector — pending refactoring suggestions
4. PHPUnit — full test suite

Auto-fix before committing: `composer quality:fix`
