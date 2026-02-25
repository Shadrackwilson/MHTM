<?php
// cron/debt_reminder.php
// This script should be set up as a daily cron job (e.g., via CPanel or command line)
// php /path/to/MHTM/cron/debt_reminder.php

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/functions.php';

// Fetch Settings
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
$reminder_day = $settings['reminder_date']; // e.g., 5th of the month

if ((int)date('d') != (int)$reminder_day) {
    // exit("Not the reminder day.");
}

// Fetch all tenants with balance > 0
$debtors = $pdo->query("SELECT t.id, t.full_name, t.phone, t.email, h.house_number, SUM(p.balance) as total_balance 
                        FROM tenants t 
                        JOIN houses h ON t.house_id = h.id 
                        JOIN payments p ON t.id = p.tenant_id 
                        WHERE t.status = 'active' 
                        GROUP BY t.id 
                        HAVING total_balance > 0")->fetchAll();

foreach ($debtors as $d) {
    $msg = "Hello {$d['full_name']}, this is a reminder from MHTM. Your current balance for {$d['house_number']} is " . number_format($d['total_balance']) . " TSH. Kindly settle your rent bill. Thank you.";
    
    // Log SMS
    $stmt = $pdo->prepare("INSERT INTO sms_logs (tenant_id, message, status) VALUES (?, ?, ?)");
    $stmt->execute([$d['id'], $msg, 'auto-sent']);
    
    // Log Email
    $stmt = $pdo->prepare("INSERT INTO email_logs (tenant_id, subject, message, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$d['id'], 'Rent Payment Reminder', $msg, 'auto-sent']);
    
    // In a real scenario, you'd call your SMS/Email API here:
    // sendSMS($d['phone'], $msg, $settings['sms_api_key']);
    // sendEmail($d['email'], 'Rent Reminder', $msg, $settings);
    
    echo "Reminder sent to: {$d['full_name']} ({$d['total_balance']} TSH)\n";
}
?>
