<?php
namespace App\Controllers;

use App\Models\User;
use App\Core\Session;
use App\Core\Validator;
use App\Core\Security;

/**
 * Authentication Controller
 * 
 * Handles user registration, login, and session management
 */
class AuthController
{
    private User $userModel;
    private Session $session;
    private Validator $validator;
    
    public function __construct()
    {
        $this->userModel = new User();
        $this->session = new Session();
        $this->validator = new Validator();
    }
    
    /**
     * Handle user registration
     */
    public function register(array $data): array
    {
        // Validate input
        $rules = [
            'username' => 'required|min:3|max:50|alphanumeric',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|strong_password',
            'confirm_password' => 'required|match:password',
            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
        ];
        
        $errors = $this->validator->validate($data, $rules);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // CSRF protection
        if (!Security::verifyCSRFToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'errors' => ['Invalid security token']];
        }
        
        try {
            // Create user
            $userId = $this->userModel->create($data);
            
            // Log activity
            $this->logActivity($userId, 'user_registered', 'users', $userId);
            
            return [
                'success' => true,
                'message' => 'Registration successful! Please login.',
                'user_id' => $userId
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'errors' => ['Registration failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Handle user login
     */
    public function login(string $email, string $password, bool $remember = false): array
    {
        // Validate input
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }
        
        // Check login attempts
        if ($this->hasTooManyLoginAttempts($email)) {
            return ['success' => false, 'error' => 'Too many login attempts. Please try again later.'];
        }
        
        // Find user
        $user = $this->userModel->findByEmail($email);
        
        if (!$user || !$this->userModel->verifyPassword($password, $user['password'])) {
            $this->incrementLoginAttempts($email);
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        
        // Clear login attempts
        $this->clearLoginAttempts($email);
        
        // Update last login
        $this->userModel->updateLastLogin($user['id']);
        
        // Create session
        $this->session->create([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
        ]);
        
        // Remember me functionality
        if ($remember) {
            $token = Security::generateRememberToken();
            $this->storeRememberToken($user['id'], $token);
            setcookie('remember_token', $token, time() + 30 * 24 * 3600, '/', '', true, true);
        }
        
        // Log activity
        $this->logActivity($user['id'], 'user_login', 'users', $user['id']);
        
        return [
            'success' => true,
            'redirect' => $this->getRedirectUrl($user['role']),
            'message' => 'Login successful'
        ];
    }
    
    /**
     * Handle logout
     */
    public function logout(): void
    {
        $userId = $this->session->get('user_id');
        
        // Log activity
        if ($userId) {
            $this->logActivity($userId, 'user_logout', 'users', $userId);
        }
        
        // Destroy session
        $this->session->destroy();
        
        // Clear remember token
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
    
    /**
     * Get redirect URL based on user role
     */
    private function getRedirectUrl(string $role): string
    {
        return match($role) {
            'admin' => '/admin/dashboard',
            'technician' => '/technician/dashboard',
            default => '/user/dashboard'
        };
    }
}