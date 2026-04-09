#!/usr/bin/env bash
# Project bootstrap script.
# Run once after cloning: bash bin/init.sh [base|dev]
#
# Usage:
#   bash bin/init.sh       # default: PostgreSQL + Redis + Mailpit
#   bash bin/init.sh base  # PostgreSQL + Redis + Mailpit (same as default)
#   bash bin/init.sh dev   # base + SonarQube

set -euo pipefail

ENV="${1:-base}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

echo "=========================================="
echo " PHP Symfony Docker Template — Bootstrap"
echo " Environment: ${ENV}"
echo "=========================================="
echo ""

# 1. Copy .env if not present
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✔ .env created from .env.example"
    echo "  ⚠  Review .env and set real secrets before production."
else
    echo "✔ .env already exists — skipping copy."
fi

# 2. Generate APP_SECRET if still placeholder
if grep -q "change_me_to_a_random_32_char_string" .env; then
    SECRET=$(openssl rand -hex 16)
    sed -i "s/change_me_to_a_random_32_char_string/${SECRET}/" .env
    echo "✔ APP_SECRET generated."
fi

# 3. Start Docker services
echo ""
echo "Starting Docker services..."

if [ "${ENV}" = "dev" ]; then
    COMPOSE_FILES="-f docker-compose.yml -f docker-compose.dev.yml"
else
    COMPOSE_FILES="-f docker-compose.yml"
fi

docker compose ${COMPOSE_FILES} up -d --build
echo "✔ Docker services started."

# 4. Wait for PostgreSQL to be ready
echo ""
echo "Waiting for PostgreSQL..."
until docker compose exec -T postgres pg_isready -U app -d app > /dev/null 2>&1; do
    printf "."
    sleep 1
done
echo " ready."

# 5. Install Composer dependencies
echo ""
echo "Installing Composer dependencies..."
docker compose exec php composer install --prefer-dist --no-progress --no-interaction
echo "✔ Composer dependencies installed."

# 6. Run database migrations (app)
echo ""
echo "Running database migrations..."
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
echo "✔ Migrations complete."

# 7. Install git hooks
echo ""
echo "Installing git hooks..."
bash bin/setup-hooks.sh
echo "✔ Git hooks installed."

# 8. Install Node dependencies (optional)
if command -v npm &>/dev/null; then
    echo ""
    echo "Installing Node dependencies..."
    npm install
    echo "✔ Node dependencies installed."
fi

echo ""
echo "=========================================="
echo " Bootstrap complete!"
echo ""
echo " Application : http://localhost:8080"
echo " API Docs    : http://localhost:8080/api/doc"
echo " Mailpit     : http://localhost:8025"
if [ "${ENV}" = "dev" ]; then
echo " SonarQube   : http://localhost:9000  (admin/admin)"
fi
echo "=========================================="
