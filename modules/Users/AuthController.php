<?php

namespace Modules\Users;

use Core\Auth;
use Core\Database;
use Core\Middleware;

class AuthController {

    /** Builds a branded HTML verification email */
    private static function buildVerifyEmail(string $username, string $verifyUrl, string $siteTitle): string {
        $safeUser = htmlspecialchars($username);
        $safeSite = htmlspecialchars($siteTitle);
        $safeUrl  = htmlspecialchars($verifyUrl);
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Verify your email — {$safeSite}</title></head>
<body style="margin:0;padding:0;background:#07070d;font-family:'Helvetica Neue',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:48px 16px;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#0f172a;border:1px solid rgba(255,255,255,.08);border-radius:16px;overflow:hidden;max-width:560px;width:100%">
        <!-- Header -->
        <tr><td style="background:linear-gradient(135deg,#6366f1,#0ea5e9);padding:32px;text-align:center;">
          <h1 style="color:#fff;font-size:1.5rem;margin:0;font-weight:800;letter-spacing:-0.5px">{$safeSite}</h1>
        </td></tr>
        <!-- Body -->
        <tr><td style="padding:40px 36px;">
          <h2 style="color:#f8fafc;font-size:1.3rem;margin:0 0 16px;">Hi {$safeUser}, welcome aboard!</h2>
          <p style="color:#94a3b8;line-height:1.7;margin:0 0 28px;">
            Thanks for signing up. To complete your registration, please verify your email address by clicking the button below.
          </p>
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr><td align="center" style="padding-bottom:28px;">
              <a href="{$safeUrl}" style="display:inline-block;background:linear-gradient(135deg,#6366f1,#0ea5e9);color:#fff;font-weight:700;font-size:1rem;padding:14px 36px;border-radius:10px;text-decoration:none;letter-spacing:0.3px">Verify My Email</a>
            </td></tr>
          </table>
          <p style="color:#64748b;font-size:0.82rem;line-height:1.6;margin:0;">
            Or copy and paste this link into your browser:<br>
            <a href="{$safeUrl}" style="color:#0ea5e9;word-break:break-all;">{$safeUrl}</a>
          </p>
          <hr style="border:none;border-top:1px solid rgba(255,255,255,.06);margin:28px 0;">
          <p style="color:#64748b;font-size:0.78rem;margin:0;">
            If you did not create an account on {$safeSite}, you can safely ignore this email.
            This link expires in 48 hours.
          </p>
        </td></tr>
        <!-- Footer -->
        <tr><td style="background:#0b1221;padding:20px 36px;text-align:center;">
          <p style="color:#475569;font-size:0.78rem;margin:0;">&copy; {$safeSite}. Sent with care.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

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

                $userId = $db->insert('users', [
                    'username'           => $username,
                    'email'              => $email,
                    'password'           => $hashed,
                    'verification_token' => $token,
                    'is_verified'        => $requireVerify ? 0 : 1,
                    'trust_level'        => $requireVerify ? 0 : 1,
                ]);

                if ($requireVerify) {
                    $verifyUrl  = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/verify-email?token=' . $token;
                    $siteTitle  = \Core\Settings::siteTitle();
                    $htmlBody   = self::buildVerifyEmail($username, $verifyUrl, $siteTitle);
                    $textBody   = "Hi {$username},\n\nPlease verify your email by clicking the link below:\n{$verifyUrl}\n\nIf you did not register on {$siteTitle}, you can safely ignore this email.";

                    $sent = \Core\Mailer::send($email, "Verify your email — {$siteTitle}", $htmlBody, $textBody);
                    if (!$sent) {
                        error_log("[Auth] Verification email failed to send for user {$username} <{$email}>");
                    }

                    // Redirect to a dedicated pending-verification page
                    $_SESSION['verify_pending_email'] = $email;
                    header('Location: /verify-pending');
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

    public function verifyPending(): void {
        $email = $_SESSION['verify_pending_email'] ?? null;
        $theme = 'antigravity';
        require ROOT_PATH . "/themes/{$theme}/pages/verify-pending.php";
    }

    public function verifyEmail(): void {
        $token = trim($_GET['token'] ?? '');
        $theme = 'antigravity';

        if (empty($token)) {
            $error = 'Invalid or missing verification token.';
            require ROOT_PATH . "/themes/{$theme}/pages/verify-result.php";
            return;
        }

        $db   = Database::getInstance();
        $user = $db->fetch(
            "SELECT id, username, email FROM users WHERE verification_token = :token AND is_verified = 0",
            ['token' => $token]
        );

        if ($user) {
            $db->query(
                "UPDATE users SET is_verified = 1, verification_token = NULL, trust_level = 1 WHERE id = :id",
                ['id' => $user['id']]
            );
            $verifySuccess = true;
            $verifyUsername = $user['username'];
        } else {
            $verifySuccess = false;
        }

        require ROOT_PATH . "/themes/{$theme}/pages/verify-result.php";
    }

    public function logout() {
        Auth::logout();
        header('Location: /login');
        exit;
    }
}
