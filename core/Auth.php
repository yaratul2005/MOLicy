<?php

namespace Core;

class Auth {
    public function __construct() {
        // Initialize Auth service
    }

    public static function check() {
        return isset($_SESSION['user_id']);
    }

    public static function user() {
        if (!self::check()) return null;

        $db = Database::getInstance();
        return $db->fetch("SELECT id, username, email, trust_level FROM users WHERE id = :id", [
            'id' => $_SESSION['user_id']
        ]);
    }

    public static function attempt($email, $password) {
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE email = :email", ['email' => $email]);

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }

        return false;
    }

    public static function logout() {
        $_SESSION = [];
        session_destroy();
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
