<?php
namespace App\Core;

/**
 * Database Connection Manager
 * 
 * Singleton pattern for database connections
 * Implements prepared statements for security
 */
class Database
{
    private static ?Database $instance = null;
    private ?\PDO $connection = null;
    
    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        $dbConfig = $config['connections'][$config['default']];
        
        try {
            $dsn = "{$dbConfig['driver']}:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
            
            $this->connection = new \PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['options']
            );
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get Database instance (Singleton)
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection(): \PDO
    {
        return $this->connection;
    }
    
    /**
     * Execute prepared statement with parameters
     */
    public function executeQuery(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Query execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }
    
    /**
     * Get last inserted ID
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}