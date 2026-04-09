#!/bin/sh
# Script to run SonarQube analysis
# Called from pre-commit hook and CI/CD pipelines

set -e

SONAR_HOST="${SONAR_HOST_URL:-http://sonarqube:9000}"
SONAR_TOKEN="${SONAR_TOKEN:-}"
PROJECT_KEY="${SONAR_PROJECT_KEY:-php-sf-template}"

echo "Running SonarQube analysis against ${SONAR_HOST}..."

if [ -z "$SONAR_TOKEN" ]; then
    echo "Warning: SONAR_TOKEN is not set. Analysis may fail."
fi

docker run --rm \
    --network host \
    -v "$(pwd):/usr/src" \
    sonarsource/sonar-scanner-cli:latest \
    -Dsonar.projectKey="${PROJECT_KEY}" \
    -Dsonar.sources=src \
    -Dsonar.tests=tests \
    -Dsonar.host.url="${SONAR_HOST}" \
    -Dsonar.token="${SONAR_TOKEN}" \
    -Dsonar.php.coverage.reportPaths=var/coverage/clover.xml \
    -Dsonar.php.tests.reportPath=var/coverage/junit.xml \
    -Dsonar.coverage.exclusions="tests/**,features/**,config/**,public/**"

echo "SonarQube analysis complete."
