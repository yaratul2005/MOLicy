<?php

namespace Modules\Users;

use Core\Auth;
use Core\Database;
use Core\Middleware;

class AuthController {

    public function showLogin() {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }
        $theme = 'antigravity';
        require ROOT_PATH . "/themes/{$theme}/pages/login.php";
    }

    public function showRegister() {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }
        $theme = 'antigravity';
        require ROOT_PATH . "/themes/{$theme}/pages/register.php";
    }

    public function login() {
        Middleware::verifyCSRF();
        Middleware::rateLimit('login_attempt', 8, 120); // 8 attempts per 2 min per IP

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $error    = null;

        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } elseif (!Auth::attempt($email, $password)) {
            $error = 'Invalid email or password. Please check your credentials.';
        } else {
            $redirect = $_SESSION['intended_url'] ?? '/';
            unset($_SESSION['intended_url']);
            header('Location: ' . $redirect);
            exit;
        }

        $theme = 'antigravity';
        require ROOT_PATH . "/themes/{$theme}/pages/login.php";
    }

    public function register() {
        Middleware::verifyCSRF();
        Middleware::rateLimit('register', 3, 600); // 3 registrations per 10 min per IP

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $error    = null;

        // Validate
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $error = 'Username must be 3–20 characters (letters, numbers, underscores only).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $db       = Database::getInstance();
            $existing = $db->fetch(
                "SELECT id FROM users WHERE email = :email OR username = :username",
                ['email' => $email, 'username' => $username]
            );

            if ($existing) {
                $error = 'That username or email is already registered. Try logging in instead.';
            } else {
                $hashed = Auth::hashPassword($password);
                $db->insert('users', [
                    'username'    => $username,
                    'email'       => $email,
                    'password'    => $hashed,
                    'trust_level' => 1,
                ]);

                Auth::attempt($email, $password);
                header('Location: /');
                exit;
            }
        }

        $theme = 'antigravity';
        require ROOT_PATH . "/themes/{$theme}/pages/register.php";
    }

    public function logout() {
        Auth::logout();
        header('Location: /login');
        exit;
    }
}
