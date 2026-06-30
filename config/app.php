<?php
/**
 * Application Configuration
 * 
 * Core application settings for the Help Desk System
 */

return [
    'name' => 'Help Desk System',
    'version' => '1.0.0',
    'environment' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'America/New_York',
    'locale' => 'en',
    
    'security' => [
        'password_min_length' => 8,
        'password_require_mixed_case' => true,
        'password_require_numbers' => true,
        'password_require_symbols' => true,
        'max_login_attempts' => 5,
        'lockout_duration_minutes' => 15,
        'session_lifetime_minutes' => 30,
        'csrf_protection' => true,
    ],
    
    'tickets' => [
        'prefix' => 'TKT',
        'number_length' => 6,
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_file_types' => [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain', 'application/zip'
        ],
        'auto_close_days' => 7, // Auto-close resolved tickets after 7 days
    ],
    
    'notifications' => [
        'email_enabled' => true,
        'from_address' => 'helpdesk@company.com',
        'from_name' => 'IT Help Desk',
        'smtp_host' => env('MAIL_HOST', 'smtp.gmail.com'),
        'smtp_port' => env('MAIL_PORT', 587),
        'smtp_encryption' => 'tls',
    ],
    
    'reports' => [
        'pdf_engine' => 'dompdf',
        'csv_delimiter' => ',',
        'cache_duration_minutes' => 30,
    ]
];