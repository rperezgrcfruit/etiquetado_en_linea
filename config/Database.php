<?php
/**
 * Database.php — Singleton PDO connection.
 *
 * Replaces the global $pdo variable with a proper singleton.
 * Existing code using `global $pdo` still works (the singleton
 * shares the same connection), but new code should use
 * Database::getInstance() instead.
 *
 * Usage:
 *   $db = Database::getInstance();
 *   $stmt = $db->query($sql, $params);
 *   $rows = $stmt->fetchAll();
 */
require_once __DIR__ . '/../vendor/autoload.php';

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private function __construct()
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();

        date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Santiago');

        if (!defined('TEMPORADA_ACTUAL')) {
            define('TEMPORADA_ACTUAL', $_ENV['TEMPORADA_ACTUAL'] ?? date('Y'));
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s',
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_NAME'] ?? 'grcfruit_grc',
            $_ENV['DB_CHARSET'] ?? 'utf8mb4'
        );
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $this->pdo = new PDO($dsn, $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '', $opt);
    }

    /**
     * Get the singleton instance.
     * Also sets $GLOBALS['pdo'] for backward compatibility with
     * existing code that uses `global $pdo`.
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
            // Backward compat: make $pdo available globally
            if (!isset($GLOBALS['pdo'])) {
                $GLOBALS['pdo'] = self::$instance->pdo;
            }
        }
        return self::$instance;
    }

    /**
     * Get the raw PDO instance (for legacy code that needs it).
     */
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a prepared query with named parameters.
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Execute a statement (INSERT/UPDATE/DELETE) and return affected rows.
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Fetch a single row.
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction.
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
}
