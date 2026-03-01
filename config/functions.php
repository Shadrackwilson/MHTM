<?php
// config/functions.php

session_start();

/**
 * Sanitize user input for XSS protection.
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if the admin is logged in.
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/**
 * Redirect to a specific page.
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Display a success/error message using session flash.
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show" role="alert">
                ' . $flash['message'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}

/**
 * CSRF Protection
 */
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return number_format($amount, 2);
}

/**
 * Send Email (Placeholder - requires PHPMailer or mail server)
 */
function sendEmail($to, $subject, $message, $settings) {
    if (empty($to)) return false;
    
    // In a real scenario, integrate PHPMailer here using $settings
    // Example:
    /*
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = $settings['email_smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $settings['email_smtp_user'];
    $mail->Password = $settings['email_smtp_pass'];
    $mail->SMTPSecure = 'tls';
    $mail->Port = $settings['email_smtp_port'];
    $mail->setFrom($settings['email_smtp_user'], $settings['system_name']);
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body = $message;
    return $mail->send();
    */

    // Fallback to native PHP mail (often fails on Localhost)
    $headers = "From: " . $settings['email_smtp_user'] . "\r\n";
    $headers .= "Reply-To: " . $settings['email_smtp_user'] . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return @mail($to, $subject, $message, $headers);
}

/**
 * Send SMS (Placeholder - requires Africa's Talking / Twilio integration)
 */
function sendSMS($phone, $message, $apiKey) {
    if (empty($phone) || empty($apiKey)) return false;
    // Call Africa's Talking API or similar here
    return true; 
}

/**
 * Send WhatsApp (Placeholder - requires Meta Cloud API integration)
 */
function sendWhatsApp($phone, $message, $token) {
    if (empty($phone) || empty($token)) return false;
    // Call Meta WhatsApp Cloud API here
    return true;
}
/**
 * Log Admin Activity
 */
function logActivity($pdo, $admin_id, $action, $details) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$admin_id, $action, $details]);
    } catch (PDOException $e) {
        // Silently fail logging if error
    }
}

/**
 * Check Admin Role Permissions
 */
function hasRole($required_roles) {
    if (!isset($_SESSION['admin_role'])) return false;
    if (is_string($required_roles)) {
        return $_SESSION['admin_role'] === $required_roles || $_SESSION['admin_role'] === 'super_admin';
    }
    return in_array($_SESSION['admin_role'], $required_roles) || $_SESSION['admin_role'] === 'super_admin';
}

/**
 * RBAC Permission Helpers
 */
function canRead() {
    return isLoggedIn(); // All admins can read
}

function canEdit() {
    return hasRole(['editor', 'manager', 'super_admin']);
}

function canManage() {
    return hasRole(['manager', 'super_admin']);
}

function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';
}
?>
