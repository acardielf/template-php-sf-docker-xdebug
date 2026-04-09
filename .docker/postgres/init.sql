-- PostgreSQL initialization script.
-- Creates the test database alongside the main application database.
-- This runs once when the container is first created.

SELECT 'CREATE DATABASE app_test OWNER app'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'app_test')\gexec
