<?php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', $_SERVER['DOCUMENT_ROOT']);
}
if (file_exists(ROOT_DIR . '/.env')) {
    # Load .env file
    Dotenv\Dotenv::create(ROOT_DIR)->load();
} else if (file_exists(__DIR__ . '/.env')) {
    # Load .env file
    Dotenv\Dotenv::create(__DIR__)->load();
}
