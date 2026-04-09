#!/usr/bin/env bash
# Installs git pre-commit hooks for code quality enforcement.
# Run this script once after cloning the repository.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
HOOKS_DIR="${ROOT_DIR}/.git/hooks"

echo "Installing git hooks..."

# Pre-commit hook
cat > "${HOOKS_DIR}/pre-commit" << 'HOOK'
#!/usr/bin/env bash
set -euo pipefail

echo "Running pre-commit quality checks..."

# Determine if we are inside Docker or running locally
if [ -f "/.dockerenv" ]; then
    PHP_CMD="php"
    VENDOR_BIN="vendor/bin"
else
    # Try to run inside the PHP container
    PHP_CMD="docker compose exec -T php php"
    VENDOR_BIN="vendor/bin"
fi

# 1. PHP CS Fixer — check formatting
echo "[1/4] PHP CS Fixer..."
${PHP_CMD} ${VENDOR_BIN}/php-cs-fixer fix --dry-run --diff --quiet
if [ $? -ne 0 ]; then
    echo "❌ PHP CS Fixer failed. Run 'composer cs:fix' to auto-fix."
    exit 1
fi
echo "✔ PHP CS Fixer passed."

# 2. PHPStan — static analysis
echo "[2/4] PHPStan..."
${PHP_CMD} ${VENDOR_BIN}/phpstan analyse --memory-limit=512M --no-progress --quiet
if [ $? -ne 0 ]; then
    echo "❌ PHPStan failed. Fix the issues above."
    exit 1
fi
echo "✔ PHPStan passed."

# 3. Rector — check for refactoring suggestions
echo "[3/4] Rector..."
${PHP_CMD} ${VENDOR_BIN}/rector process --dry-run --quiet
if [ $? -ne 0 ]; then
    echo "❌ Rector found issues. Run 'composer rector:fix' to auto-fix."
    exit 1
fi
echo "✔ Rector passed."

# 4. PHPUnit — run test suite
echo "[4/4] PHPUnit..."
${PHP_CMD} bin/phpunit --no-coverage --testdox --colors=never
if [ $? -ne 0 ]; then
    echo "❌ Tests failed. Fix failing tests before committing."
    exit 1
fi
echo "✔ Tests passed."

# 5. SonarQube analysis (optional — runs in background if configured)
if [ -n "${SONAR_TOKEN:-}" ]; then
    echo "[5/5] SonarQube (background)..."
    ${PHP_CMD} bin/phpunit --coverage-clover var/coverage/clover.xml --log-junit var/coverage/junit.xml --no-progress 2>/dev/null &
    bash .docker/sonarqube/sonar-scanner.sh &
    echo "✔ SonarQube analysis dispatched in background."
fi

echo "All pre-commit checks passed!"
HOOK

chmod +x "${HOOKS_DIR}/pre-commit"

echo "✔ pre-commit hook installed at ${HOOKS_DIR}/pre-commit"
echo ""
echo "Done! Quality checks will run automatically before each commit."
