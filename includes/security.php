<?php
/**
 * Security Helper Functions
 * Provides CSRF protection, input sanitization, and session security
 */

/**
 * Generate CSRF token and store in session
 */
function generate_csrf_token()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token)
{
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token input field HTML
 */
function csrf_field()
{
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Sanitize input string
 */
function sanitize_input($input)
{
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }

    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Validate email address
 */
function validate_email($email)
{
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate username (alphanumeric, underscore, hyphen, 3-20 chars)
 */
function validate_username($username)
{
    return preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username);
}

/**
 * Validate password strength
 * Minimum 8 characters, at least one letter and one number
 */
function validate_password_strength($password)
{
    if (strlen($password) < 8) {
        return false;
    }

    // At least one letter and one number
    if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return false;
    }

    return true;
}

/**
 * Hash password using bcrypt
 */
function hash_password($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 */
function verify_password($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Regenerate session ID for security
 */
function regenerate_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Rate limiting for login attempts
 * Returns true if rate limit exceeded
 */
function check_rate_limit($identifier, $max_attempts = 5, $time_window = 900)
{
    $rate_limit_file = __DIR__ . '/../data/rate_limits.json';
    $rate_limits = [];

    // Crea il file se non esiste
    if (!file_exists($rate_limit_file)) {
        @file_put_contents($rate_limit_file, json_encode([], JSON_PRETTY_PRINT));
    }

    if (file_exists($rate_limit_file)) {
        $rate_limits = json_decode(file_get_contents($rate_limit_file), true) ?? [];
    }

    $current_time = time();
    $identifier_key = md5($identifier);

    // Clean old entries
    $rate_limits = array_filter($rate_limits, function ($entry) use ($current_time, $time_window) {
        return ($current_time - $entry['first_attempt']) < $time_window;
    });

    // Check if identifier exists
    if (!isset($rate_limits[$identifier_key])) {
        $rate_limits[$identifier_key] = [
            'attempts' => 1,
            'first_attempt' => $current_time
        ];
        @file_put_contents($rate_limit_file, json_encode($rate_limits, JSON_PRETTY_PRINT));
        return false;
    }

    // Check if within time window
    if (($current_time - $rate_limits[$identifier_key]['first_attempt']) > $time_window) {
        // Reset counter
        $rate_limits[$identifier_key] = [
            'attempts' => 1,
            'first_attempt' => $current_time
        ];
        @file_put_contents($rate_limit_file, json_encode($rate_limits, JSON_PRETTY_PRINT));
        return false;
    }

    // Increment attempts
    $rate_limits[$identifier_key]['attempts']++;
    @file_put_contents($rate_limit_file, json_encode($rate_limits, JSON_PRETTY_PRINT));

    // Check if exceeded
    return $rate_limits[$identifier_key]['attempts'] > $max_attempts;
}

/**
 * Reset rate limit for identifier
 */
function reset_rate_limit($identifier)
{
    $rate_limit_file = __DIR__ . '/../data/rate_limits.json';
    $rate_limits = [];

    if (file_exists($rate_limit_file)) {
        $rate_limits = json_decode(file_get_contents($rate_limit_file), true) ?? [];
    }

    $identifier_key = md5($identifier);
    unset($rate_limits[$identifier_key]);

    @file_put_contents($rate_limit_file, json_encode($rate_limits, JSON_PRETTY_PRINT));
}

/**
 * Prevent JSON injection by validating input
 */
function prevent_json_injection($input)
{
    // Remove any control characters that could break JSON
    $input = preg_replace('/[\x00-\x1F\x7F]/', '', $input);

    // Escape special JSON characters
    $input = str_replace(['\\', '"'], ['\\\\', '\\"'], $input);

    return $input;
}

/**
 * Secure session configuration
 */
function configure_secure_session()
{
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.cookie_samesite', 'Strict');
}
