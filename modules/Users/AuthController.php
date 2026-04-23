<?php

namespace Modules\Users;

use Core\Auth;
use Core\Database;
use Core\Middleware;

class AuthController {

    private function verifyRecaptcha(): bool {
        if (\Core\Settings::get('enable_recaptcha') !== '1') {
            return true;
        }

        $secret = \Core\Settings::get('recaptcha_secret_key');
        if (empty($secret)) {
            return true; // fail open if misconfigured
        }

        $response = $_POST['g-recaptcha-response'] ?? '';
        if (empty($response)) {
            return false;
        }

        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query([
            'secret'   => $secret,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ]));
        curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($verify);
        curl_close($verify);

        if (!$result) return false;
        $decoded = json_decode($result, true);
        return $decoded['success'] ?? false;
    }

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

        if (!$this->verifyRecaptcha()) {
            $error = 'Please complete the reCAPTCHA verification.';
        } elseif (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } else {
            $db = Database::getInstance();
            $user = $db->fetch("SELECT * FROM users WHERE email = :email", ['email' => $email]);
            
            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Invalid email or password. Please check your credentials.';
            } elseif (isset($user['is_verified']) && $user['is_verified'] == 0) {
                $error = 'You must verify your email address before logging in. Please check your inbox.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['trust_level'] = $user['trust_level'];
                
                $redirect = $_SESSION['intended_url'] ?? '/';
                unset($_SESSION['intended_url']);
                header('Location: ' . $redirect);
                exit;
            }
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
        if (!$this->verifyRecaptcha()) {
            $error = 'Please complete the reCAPTCHA verification.';
        } elseif (empty($username) || empty($email) || empty($password)) {
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
                $requireVerify = \Core\Settings::get('require_email_verify') === '1';
                $token = $requireVerify ? bin2hex(random_bytes(32)) : null;

                $db->insert('users', [
                    'username'           => $username,
                    'email'              => $email,
                    'password'           => $hashed,
                    'verification_token' => $token,
                    'is_verified'        => $requireVerify ? 0 : 1,
                    'trust_level'        => $requireVerify ? 0 : 1,
                ]);

                if ($requireVerify) {
                    $verifyUrl = "https://" . $_SERVER['HTTP_HOST'] . "/verify-email?token=" . $token;
                    $htmlBody = "<h2>Welcome, {$username}!</h2><p>Please click the link below to verify your email:</p><p><a href='{$verifyUrl}'>{$verifyUrl}</a></p>";
                    \Core\Mailer::send($email, "Verify Your Account", $htmlBody);
                    $success = 'Registration successful! Please check your email to verify your account.';
                    $theme = 'antigravity';
                    require ROOT_PATH . "/themes/{$theme}/pages/register.php";
                    exit;
                }

                Auth::attempt($email, $password);
                header('Location: /');
                exit;
            }
        }

        $theme = 'antigravity';
        require ROOT_PATH . "/themes/{$theme}/pages/register.php";
    }

    public function verifyEmail() {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            die('Invalid token.');
        }
        
        $db = Database::getInstance();
        $user = $db->fetch("SELECT id FROM users WHERE verification_token = :token AND is_verified = 0", ['token' => $token]);
        
        if ($user) {
            $db->query("UPDATE users SET is_verified = 1, verification_token = NULL, trust_level = 1 WHERE id = :id", ['id' => $user['id']]);
            $success = "Email successfully verified! You can now log in.";
        } else {
            $error = "Invalid or expired verification token.";
        }
        
        $theme = 'antigravity';
        require ROOT_PATH . "/themes/{$theme}/pages/login.php";
    }

    public function logout() {
        Auth::logout();
        header('Location: /login');
        exit;
    }
}
