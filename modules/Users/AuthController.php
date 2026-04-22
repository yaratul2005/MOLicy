<?php

namespace Modules\Users;

use Core\Auth;

class AuthController {
    public function showLogin() {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }

        $theme = 'antigravity';
        $viewPath = ROOT_PATH . "/themes/{$theme}/pages/login.php";
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "Login view not found.";
        }
    }

    public function showRegister() {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }

        $theme = 'antigravity';
        $viewPath = ROOT_PATH . "/themes/{$theme}/pages/register.php";
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "Register view not found.";
        }
    }

    public function login() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (Auth::attempt($email, $password)) {
            header('Location: /');
        } else {
            echo "Invalid credentials. <a href='/login'>Try again</a>";
        }
    }

    public function register() {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            die("All fields are required. <a href='/register'>Try again</a>");
        }

        $db = \Core\Database::getInstance();
        
        // Check if exists
        $existing = $db->fetch("SELECT id FROM users WHERE email = :email OR username = :username", [
            'email' => $email,
            'username' => $username
        ]);

        if ($existing) {
            die("Username or Email already exists. <a href='/register'>Try again</a>");
        }

        $hashed = Auth::hashPassword($password);
        $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashed,
            'trust_level' => 1
        ]);

        Auth::attempt($email, $password);
        header('Location: /');
    }

    public function logout() {
        Auth::logout();
        header('Location: /');
    }
}
