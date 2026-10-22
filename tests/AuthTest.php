<?php
// tests/AuthTest.php
require_once __DIR__ . '/../security/Csrf.php';

class AuthTest {
    private $db;

    public function __construct() {
        // Setup SQLite in-memory DB for tests
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->setupSchema();
    }

    private function setupSchema() {
        $this->db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE,
                password_hash TEXT,
                full_name TEXT,
                role TEXT DEFAULT 'Member',
                status TEXT DEFAULT 'Active'
            )
        ");

        $this->db->exec("
            CREATE TABLE rate_limit (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_hash TEXT,
                action TEXT,
                attempt_time INTEGER
            )
        ");
    }

    public function run() {
        echo "Running AuthTest...\n";
        $this->testPasswordHashing();
        $this->testLoginFlow();
        $this->testCsrfGeneration();
        echo "AuthTest Passed!\n";
    }

    private function testPasswordHashing() {
        $password = 'SecretPassword123!';
        $hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);

        if (!password_verify($password, $hash)) {
            throw new Exception("Password hashing or verification failed.");
        }
    }

    private function testLoginFlow() {
        $email = 'test@example.com';
        $password = 'password123';
        $hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);

        $stmt = $this->db->prepare("INSERT INTO users (email, password_hash, full_name) VALUES (?, ?, ?)");
        $stmt->execute([$email, $hash, 'Test User']);

        // Test valid login
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception("Valid login flow failed.");
        }

        // Test invalid login
        if (password_verify('wrongpassword', $user['password_hash'])) {
             throw new Exception("Invalid login falsely succeeded.");
        }
    }

    private function testCsrfGeneration() {
        // Mock session
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $token1 = Csrf::generateToken();
        if (empty($token1)) {
            throw new Exception("CSRF token generation failed.");
        }

        if (!Csrf::validateToken($token1)) {
            throw new Exception("CSRF token validation failed for valid token.");
        }

        if (Csrf::validateToken('invalid_token')) {
            throw new Exception("CSRF token validation succeeded for invalid token.");
        }
    }
}

$test = new AuthTest();
$test->run();
