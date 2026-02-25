<?php
// admin/settings.php
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$page_title = "System Settings";

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $system_name = sanitize($_POST['system_name']);
        $sms_api_key = sanitize($_POST['sms_api_key']);
        $email_smtp_host = sanitize($_POST['email_smtp_host']);
        $email_smtp_user = sanitize($_POST['email_smtp_user']);
        $email_smtp_pass = sanitize($_POST['email_smtp_pass']);
        $email_smtp_port = sanitize($_POST['email_smtp_port']);
        $whatsapp_api_key = sanitize($_POST['whatsapp_api_key']);
        $reminder_date = (int)$_POST['reminder_date'];

        $stmt = $pdo->prepare("UPDATE settings SET 
            system_name = ?, 
            sms_api_key = ?, 
            email_smtp_host = ?, 
            email_smtp_user = ?, 
            email_smtp_pass = ?, 
            email_smtp_port = ?, 
            whatsapp_api_key = ?, 
            reminder_date = ? 
            WHERE id = 1");
        
        if ($stmt->execute([$system_name, $sms_api_key, $email_smtp_host, $email_smtp_user, $email_smtp_pass, $email_smtp_port, $whatsapp_api_key, $reminder_date])) {
            setFlash('success', 'Settings updated successfully!');
        } else {
            setFlash('danger', 'Error updating settings!');
        }
    }
    redirect('settings.php');
}

// Fetch Settings
$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();

include '../includes/header.php';
?>

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body">
        <h4 class="fw-bold mb-4">System Configuration</h4>
        <form action="" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">System Name (MHTM)</label>
                    <input type="text" name="system_name" class="form-control" value="<?php echo $settings['system_name']; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Monthly Reminder Day (1-28)</label>
                    <input type="number" name="reminder_date" class="form-control" value="<?php echo $settings['reminder_date']; ?>" min="1" max="28" required>
                </div>
                <div class="col-md-12">
                    <hr class="my-4">
                    <h5 class="fw-bold text-primary mb-3"><i class="fas fa-sms me-2"></i> SMS API (Africa's Talking / Twilio)</h5>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">API Key / Token</label>
                    <input type="password" name="sms_api_key" class="form-control" value="<?php echo $settings['sms_api_key']; ?>">
                </div>
                
                <div class="col-md-12">
                    <hr class="my-4">
                    <h5 class="fw-bold text-info mb-3"><i class="fas fa-envelope me-2"></i> Email SMTP (PHPMailer)</h5>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="email_smtp_host" class="form-control" value="<?php echo $settings['email_smtp_host']; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">SMTP User / Email</label>
                    <input type="email" name="email_smtp_user" class="form-control" value="<?php echo $settings['email_smtp_user']; ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">SMTP Port</label>
                    <input type="number" name="email_smtp_port" class="form-control" value="<?php echo $settings['email_smtp_port']; ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">SMTP Pass</label>
                    <input type="password" name="email_smtp_pass" class="form-control" value="<?php echo $settings['email_smtp_pass']; ?>">
                </div>

                <div class="col-md-12">
                    <hr class="my-4">
                    <h5 class="fw-bold text-success mb-3"><i class="fab fa-whatsapp me-2"></i> WhatsApp Cloud API</h5>
                </div>
                <div class="col-md-12 mb-4">
                    <label class="form-label">Access Token</label>
                    <input type="password" name="whatsapp_api_key" class="form-control" value="<?php echo $settings['whatsapp_api_key']; ?>">
                </div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" name="update_settings" class="btn btn-primary rounded-pill py-2">Update Configuration</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
