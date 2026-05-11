<?php
/**
 * Database Configuration
 * Contains database connection settings and PDO instance
 */

// Simple manual .env parser
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

// Load from $_ENV with fallbacks
$servername = $_ENV['DB_HOST'] ?? "localhost";
$username   = $_ENV['DB_USER'] ?? "root";
$password   = $_ENV['DB_PASS'] ?? "";
$database   = $_ENV['DB_NAME'] ?? "book_exchange";
$base_url   = $_ENV['BASE_URL'] ?? "/";

// Base URL configuration
define('BASE_URL', $base_url);

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$servername;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Helper function to create proper URLs
 * @param string $path
 * @return string
 */
function url($path = '') {
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');
    return $base . ($path ? '/' . $path : '');
}
?>