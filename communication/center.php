<?php
// communication/center.php
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$page_title = "Communication Center";

// Fetch All Tenants for general messaging
$allTenants = $pdo->query("SELECT t.*, h.house_number FROM tenants t JOIN houses h ON t.house_id = h.id WHERE t.status = 'active' ORDER BY t.full_name ASC")->fetchAll();

// Fetch Tenants with Debt for quick messaging
$tenantsWithDebt = $pdo->query("SELECT t.*, h.house_number, SUM(p.balance) as total_balance 
                                 FROM tenants t 
                                 JOIN houses h ON t.house_id = h.id 
                                 JOIN payments p ON t.id = p.tenant_id 
                                 WHERE p.balance > 0 
                                 GROUP BY t.id")->fetchAll();

// Fetch Recent Expenses to link bills
$recentExpenses = $pdo->query("SELECT e.*, h.house_number FROM expenses e LEFT JOIN houses h ON e.house_id = h.id ORDER BY e.id DESC LIMIT 10")->fetchAll();

// Fetch Recent Payments for receipts
$recentPayments = $pdo->query("SELECT p.*, t.full_name, h.house_number FROM payments p JOIN tenants t ON p.tenant_id = t.id JOIN houses h ON t.house_id = h.id ORDER BY p.id DESC LIMIT 15")->fetchAll();

// Fetch System Settings for APIs
$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();

// Handle Communication Actions (SMS, Email, WhatsApp)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenant_id = $_POST['tenant_id'];
    $message = sanitize($_POST['message']);
    $type = $_POST['type']; // 'sms', 'email', 'whatsapp'

    if ($type === 'sms') {
        $tenant = $pdo->query("SELECT phone FROM tenants WHERE id = $tenant_id")->fetch();
        $status = sendSMS($tenant['phone'], $message, $settings['sms_api_key']) ? 'sent' : 'failed';
        
        $stmt = $pdo->prepare("INSERT INTO sms_logs (tenant_id, message, status) VALUES (?, ?, ?)");
        $stmt->execute([$tenant_id, $message, $status]);
        setFlash($status == 'sent' ? 'success' : 'danger', "SMS $status!");
    } elseif ($type === 'email') {
        $subject = sanitize($_POST['subject']);
        $tenant = $pdo->query("SELECT email FROM tenants WHERE id = $tenant_id")->fetch();
        $status = sendEmail($tenant['email'], $subject, $message, $settings) ? 'sent' : 'failed';

        $stmt = $pdo->prepare("INSERT INTO email_logs (tenant_id, subject, message, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $subject, $message, $status]);
        setFlash($status == 'sent' ? 'success' : 'danger', "Email $status!");
    } elseif ($type === 'whatsapp') {
        $tenant = $pdo->query("SELECT whatsapp_number FROM tenants WHERE id = $tenant_id")->fetch();
        $status = sendWhatsApp($tenant['whatsapp_number'], $message, $settings['whatsapp_api_key']) ? 'sent' : 'failed';

        $stmt = $pdo->prepare("INSERT INTO whatsapp_logs (tenant_id, message, status) VALUES (?, ?, ?)");
        $stmt->execute([$tenant_id, $message, $status]);
        setFlash($status == 'sent' ? 'success' : 'danger', "WhatsApp message $status!");
    }
    if (isset($_POST['delete_log'])) {
        $id = $_POST['log_id'];
        $log_type = $_POST['log_type'];
        $table = '';
        if ($log_type == 'SMS') $table = 'sms_logs';
        elseif ($log_type == 'Email') $table = 'email_logs';
        elseif ($log_type == 'WA') $table = 'whatsapp_logs';

        if ($table) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Log entry deleted successfully!');
        }
    }
    redirect('center.php');
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <h5 class="fw-bold mb-4">Message Templates</h5>
                <div class="list-group list-group-flush">
                    <button class="list-group-item list-group-item-action border-0 mb-2 rounded shadow-sm template-btn" 
                            data-message="Hello [NAME], this is a reminder regarding your rent for [HOUSE]. Your current balance is [BALANCE] TSH. Kindly pay as soon as possible. Thank you.">
                        <i class="fas fa-bell me-2 text-warning"></i> Debt Reminder
                    </button>
                    <button class="list-group-item list-group-item-action border-0 mb-2 rounded shadow-sm template-btn" 
                            data-message="Hello [NAME], thank you for the payment of [AMOUNT_PAID] TSH for the month of [MONTH]. Your receipt # is [RECEIPT_NO]. You can download it here: [RECEIPT_LINK]. Regards, MHTM.">
                        <i class="fas fa-check-circle me-2 text-success"></i> Payment Receipt
                    </button>
                    <button class="list-group-item list-group-item-action border-0 mb-2 rounded shadow-sm template-btn" 
                            data-message="Hello [NAME], this is a bill for [CATEGORY] at [HOUSE]. Amount: [EXPENSE_AMOUNT] TSH. Description: [EXPENSE_DESC]. Kindly settle this at your earliest convenience.">
                        <i class="fas fa-file-invoice-dollar me-2 text-danger"></i> Expense Bill
                    </button>
                    <button class="list-group-item list-group-item-action border-0 mb-2 rounded shadow-sm template-btn" 
                            data-message="Hello [NAME], your rental contract for [HOUSE] is ready. You can view it here: [CONTRACT_LINK]. Please review and let us know if you have any questions.">
                        <i class="fas fa-file-contract me-2 text-primary"></i> Send Contract
                    </button>
                    <button class="list-group-item list-group-item-action border-0 rounded shadow-sm template-btn" 
                            data-message="Dear Tenants, please be informed that [DESCRIPTION] will occur on [DATE]. Kindly take necessary precautions. MHTM Admin.">
                        <i class="fas fa-info-circle me-2 text-info"></i> Maintenance Notice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-4">Send New Notification</h5>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" id="msg_type" class="form-select" onchange="toggleSubject()">
                            <option value="sms">SMS</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="email">Email</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Recipient</label>
                        <select name="tenant_id" id="recipient" class="form-select" required>
                            <option value="">Choose Tenant...</option>
                            <optgroup label="Tenants with Debt">
                                <?php foreach ($tenantsWithDebt as $t): ?>
                                    <option value="<?php echo $t['id']; ?>" 
                                            data-name="<?php echo $t['full_name']; ?>" 
                                            data-house="<?php echo $t['house_number']; ?>" 
                                            data-balance="<?php echo $t['total_balance']; ?>">
                                        <?php echo $t['full_name']; ?> (Debt: <?php echo formatCurrency($t['total_balance']); ?> TSH)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="All Active Tenants">
                                <?php foreach ($allTenants as $t): ?>
                                    <option value="<?php echo $t['id']; ?>" 
                                            data-name="<?php echo $t['full_name']; ?>" 
                                            data-house="<?php echo $t['house_number']; ?>" 
                                            data-balance="0">
                                        <?php echo $t['full_name']; ?> (<?php echo $t['house_number']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link Expense (For Expense Bill template)</label>
                        <select id="expense_link" class="form-select">
                            <option value="">Select Expense...</option>
                            <?php foreach ($recentExpenses as $e): ?>
                                <option value="<?php echo $e['id']; ?>" 
                                        data-cat="<?php echo $e['category']; ?>" 
                                        data-amt="<?php echo $e['amount']; ?>"
                                        data-desc="<?php echo $e['description']; ?>"
                                        data-house="<?php echo $e['house_number']; ?>">
                                    <?php echo $e['category']; ?> - <?php echo formatCurrency($e['amount']); ?> (<?php echo $e['house_number'] ?: 'General'; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link Payment (For Payment Receipt template)</label>
                        <select id="payment_link" class="form-select">
                            <option value="">Select Payment Record...</option>
                            <?php foreach ($recentPayments as $p): ?>
                                <option value="<?php echo $p['id']; ?>" 
                                        data-tenant_id="<?php echo $p['tenant_id']; ?>"
                                        data-receipt="<?php echo $p['receipt_number']; ?>" 
                                        data-amt="<?php echo $p['amount_paid']; ?>"
                                        data-month="<?php echo date('M Y', strtotime($p['payment_month'])); ?>">
                                    <?php echo $p['full_name']; ?> - <?php echo formatCurrency($p['amount_paid']); ?> (<?php echo $p['receipt_number']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="subject_group" class="mb-3 d-none">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Email Subject">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" id="msg_textarea" class="form-control" rows="6" required></textarea>
                        <small class="text-muted d-block mt-2">
                            Tags: <code class="bg-light p-1">[NAME]</code>, <code class="bg-light p-1">[HOUSE]</code>, <code class="bg-light p-1">[BALANCE]</code>, 
                            <code class="bg-light p-1">[CATEGORY]</code>, <code class="bg-light p-1">[EXPENSE_AMOUNT]</code>, <code class="bg-light p-1">[EXPENSE_DESC]</code>,
                            <code class="bg-light p-1">[CONTRACT_LINK]</code>, <code class="bg-light p-1">[RECEIPT_LINK]</code>, <code class="bg-light p-1">[RECEIPT_NO]</code>, <code class="bg-light p-1">[AMOUNT_PAID]</code>
                        </small>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-pill py-2">
                             <i class="fas fa-paper-plane me-2"></i> Send Notification
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <h5 class="fw-bold mb-4">Recent Communication Logs</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Recipient</th>
                                <th>Message Snippet</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $query = "(SELECT id, 'SMS' as type, tenant_id, message, sent_at as dt, status FROM sms_logs) 
                                      UNION 
                                      (SELECT id, 'Email' as type, tenant_id, message, sent_at as dt, status FROM email_logs) 
                                      UNION 
                                      (SELECT id, 'WA' as type, tenant_id, message, sent_at as dt, status FROM whatsapp_logs) 
                                      ORDER BY dt DESC LIMIT 20";
                            $logs = $pdo->query("SELECT l.*, t.full_name FROM ($query) l LEFT JOIN tenants t ON l.tenant_id = t.id")->fetchAll();
                            
                            foreach ($logs as $log): ?>
                                <tr>
                                    <td><span class="badge <?php echo $log['type'] == 'SMS' ? 'bg-primary' : ($log['type'] == 'WA' ? 'bg-success' : 'bg-info'); ?>"><?php echo $log['type']; ?></span></td>
                                    <td class="fw-bold"><?php echo $log['full_name'] ?: 'System/General'; ?></td>
                                    <td><?php echo substr($log['message'], 0, 30); ?>...</td>
                                    <td><small><?php echo date('d/m/y H:i', strtotime($log['dt'])); ?></small></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-info view-log-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewLogModal"
                                                    data-type="<?php echo $log['type']; ?>"
                                                    data-name="<?php echo $log['full_name']; ?>"
                                                    <?php if($log['type'] == 'Email'): ?> data-subject="Subject: <?php echo $pdo->query("SELECT subject FROM email_logs WHERE id = ".$log['id'])->fetchColumn(); ?>" <?php endif; ?>
                                                    data-msg="<?php echo htmlspecialchars($log['message']); ?>"
                                                    data-date="<?php echo date('d M Y, H:i', strtotime($log['dt'])); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <form action="" method="POST" onsubmit="return confirm('Delete this log?');" style="display:inline;">
                                                <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                                <input type="hidden" name="log_type" value="<?php echo $log['type']; ?>">
                                                <button type="submit" name="delete_log" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSubject() {
    const type = document.getElementById('msg_type').value;
    const group = document.getElementById('subject_group');
    if (type === 'email') {
        group.classList.remove('d-none');
    } else {
        group.classList.add('d-none');
    }
}


document.querySelectorAll('.template-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        let msg = this.dataset.message;
        const sel = document.getElementById('recipient');
        const opt = sel.options[sel.selectedIndex];
        
        const expSel = document.getElementById('expense_link');
        const expOpt = expSel.options[expSel.selectedIndex];
        
        const paySel = document.getElementById('payment_link');
        const payOpt = paySel.options[paySel.selectedIndex];
        
        const baseUrl = window.location.origin + window.location.pathname.replace('/communication/center.php', '');

        if (sel.value) {
            msg = msg.replace(/\[NAME\]/g, opt.dataset.name);
            msg = msg.replace(/\[HOUSE\]/g, opt.dataset.house);
            msg = msg.replace(/\[BALANCE\]/g, opt.dataset.balance);
            
            // Generate contract link
            const contractLink = baseUrl + '/reports/contract.php?id=' + sel.value;
            msg = msg.replace(/\[CONTRACT_LINK\]/g, contractLink);
        }

        if (expSel.value) {
            msg = msg.replace(/\[CATEGORY\]/g, expOpt.dataset.cat);
            msg = msg.replace(/\[EXPENSE_AMOUNT\]/g, new Intl.NumberFormat().format(expOpt.dataset.amt));
            msg = msg.replace(/\[EXPENSE_DESC\]/g, expOpt.dataset.desc || 'Repair/Maintenance');
        }

        if (paySel.value) {
            msg = msg.replace(/\[RECEIPT_NO\]/g, payOpt.dataset.receipt);
            msg = msg.replace(/\[AMOUNT_PAID\]/g, new Intl.NumberFormat().format(payOpt.dataset.amt));
            msg = msg.replace(/\[MONTH\]/g, payOpt.dataset.month);
            
            const receiptLink = baseUrl + '/reports/receipt.php?id=' + paySel.value;
            msg = msg.replace(/\[RECEIPT_LINK\]/g, receiptLink);
            
            // Auto-select recipient if not selected
            if (!sel.value) {
                sel.value = payOpt.dataset.tenant_id;
            }
        }
        
        document.getElementById('msg_textarea').value = msg;
    });
});

document.querySelectorAll('.view-log-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('view_log_type').innerText = this.dataset.type;
        document.getElementById('view_log_name').innerText = this.dataset.name;
        document.getElementById('view_log_date').innerText = this.dataset.date;
        document.getElementById('view_log_subject').innerText = this.dataset.subject || '';
        document.getElementById('view_log_body').innerText = this.dataset.msg;
    });
});
</script>

<!-- View Log Modal -->
<div class="modal fade" id="viewLogModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <span id="view_log_type" class="badge bg-primary"></span>
                    <span class="text-muted ms-2" id="view_log_date"></span>
                </div>
                <h6 class="fw-bold mb-1">Recipient: <span id="view_log_name" class="text-primary"></span></h6>
                <div id="view_log_subject" class="fw-bold text-muted small mb-3"></div>
                <div class="p-3 bg-light rounded-3 border">
                    <p id="view_log_body" class="mb-0" style="white-space: pre-wrap;"></p>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
