<?php
// reports/receipt.php
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Payment ID missing!");
}

// Fetch Payment and Tenant Info
$stmt = $pdo->prepare("SELECT p.*, t.full_name, t.phone, t.email, h.house_number, h.rent_amount 
                       FROM payments p 
                       JOIN tenants t ON p.tenant_id = t.id 
                       JOIN houses h ON t.house_id = h.id 
                       WHERE p.id = ?");
$stmt->execute([$id]);
$payment = $stmt->fetch();

if (!$payment) {
    die("Payment record not found!");
}

// Fetch System Settings
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
$system_name = $settings['system_name'] ?? 'MWAKASEGE HOUSE TENANT MANAGEMENT (MHTM)';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $payment['receipt_number']; ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #f0f0f0; padding: 20px; }
        .receipt-card { background: #fff; width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px dashed #000; margin-bottom: 20px; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 1.2rem; }
        .details { margin-bottom: 20px; line-height: 1.6; }
        .details div { display: flex; justify-content: space-between; }
        .amount-section { border-top: 2px dashed #000; padding-top: 10px; margin-top: 20px; }
        .total { font-weight: bold; font-size: 1.2rem; }
        .footer { text-align: center; margin-top: 30px; font-size: 0.8rem; }
        .no-print { text-align: center; margin-bottom: 20px; }
        @media print {
            .no-print { display: none; }
            body { background: #fff; padding: 0; }
            .receipt-card { border: none; box-shadow: none; width: 100%; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">Print Receipt</button>
    <a href="../admin/payments.php">Back to Payments</a>
</div>

<div class="receipt-card">
    <div class="header">
        <h2><?php echo $system_name; ?></h2>
        <p>Official Rent Receipt</p>
    </div>

    <div class="details">
        <div><span>Receipt #:</span> <strong><?php echo $payment['receipt_number']; ?></strong></div>
        <div><span>Date:</span> <strong><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></strong></div>
        <hr>
        <div><span>Tenant:</span> <strong><?php echo $payment['full_name']; ?></strong></div>
        <div><span>House:</span> <strong><?php echo $payment['house_number']; ?></strong></div>
        <hr>
        <div><span>Rent (Monthly):</span> <strong><?php echo formatCurrency($payment['rent_amount']); ?> TSH</strong></div>
        <div><span>Cycle Rent (3 Months):</span> <strong><?php echo formatCurrency($payment['rent_amount'] * 3); ?> TSH</strong></div>
        <div><span>Payment Period:</span> <strong><?php echo date('M Y', strtotime($payment['payment_month'])) . ' - ' . date('M Y', strtotime($payment['payment_month'] . ' + 2 months')); ?></strong></div>
    </div>

    <div class="amount-section">
        <div class="total"><span>Amount Paid:</span> <span><?php echo formatCurrency($payment['amount_paid']); ?> TSH</span></div>
        <div style="color: red;"><span>Balance:</span> <span><?php echo formatCurrency($payment['balance']); ?> TSH</span></div>
    </div>

    <div class="footer">
        <p>Thank you for choosing MHTM.</p>
        <p>Generated on <?php echo date('d/m/Y H:i:s'); ?></p>
        <div style="margin-top: 20px; border-top: 1px solid #ccc; width: 150px; margin-left: auto; margin-right: auto; padding-top: 5px;">
            Authorized Signature
        </div>
    </div>
</div>

</body>
</html>
