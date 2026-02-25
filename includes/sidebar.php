<!-- includes/sidebar.php -->
<nav id="sidebar">
    <div class="sidebar-header">
        <h4>MHTM Admin</h4>
    </div>

    <ul class="list-unstyled components">
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="/MHTM/dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'houses.php' ? 'active' : ''; ?>">
            <a href="/MHTM/admin/houses.php">
                <i class="fas fa-home"></i> House Management
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'tenants.php' ? 'active' : ''; ?>">
            <a href="/MHTM/admin/tenants.php">
                <i class="fas fa-users"></i> Tenant Management
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>">
            <a href="/MHTM/admin/payments.php">
                <i class="fas fa-money-bill-wave"></i> Payment Management
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'expenses.php' ? 'active' : ''; ?>">
            <a href="/MHTM/admin/expenses.php">
                <i class="fas fa-receipt"></i> Expense Management
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'communication.php' ? 'active' : ''; ?>">
            <a href="/MHTM/communication/center.php">
                <i class="fas fa-paper-plane"></i> Communication Center
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <a href="/MHTM/reports/index.php">
                <i class="fas fa-chart-line"></i> Reports & Stats
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <a href="/MHTM/admin/settings.php">
                <i class="fas fa-cog"></i> System Settings
            </a>
        </li>
    </ul>
</nav>
