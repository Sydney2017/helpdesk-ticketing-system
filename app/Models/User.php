<?php
namespace App\Models;

use App\Core\Database;

/**
 * User Model
 * 
 * Handles all user-related database operations
 * Implements password hashing and validation
 */
class User
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create new user
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO users (username, email, password, first_name, last_name, role, department) 
                VALUES (:username, :email, :password, :first_name, :last_name, :role, :department)";
        
        $params = [
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':role' => $data['role'] ?? 'user',
            ':department' => $data['department'] ?? null,
        ];
        
        $this->db->executeQuery($sql, $params);
        return (int) $this->db->lastInsertId();
    }
    
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM users WHERE email = :email AND status = 'active' LIMIT 1";
        $result = $this->db->executeQuery($sql, [':email' => $email]);
        
        $user = $result->fetch();
        return $user ?: null;
    }
    
    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $result = $this->db->executeQuery($sql, [':id' => $id]);
        
        $user = $result->fetch();
        return $user ?: null;
    }
    
    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $userId): void
    {
        $sql = "UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id";
        $this->db->executeQuery($sql, [':id' => $userId]);
    }
    
    /**
     * Get all users by role
     */
    public function getByRole(string $role): array
    {
        $sql = "SELECT id, username, email, first_name, last_name, department, status, created_at 
                FROM users WHERE role = :role ORDER BY created_at DESC";
        
        return $this->db->executeQuery($sql, [':role' => $role])->fetchAll();
    }
    
    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): void
    {
        $fields = [];
        $params = [':id' => $userId];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['first_name', 'last_name', 'phone', 'department'])) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (!empty($fields)) {
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
            $this->db->executeQuery($sql, $params);
        }
    }
}