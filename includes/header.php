<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>Injector Manager</h2>
        <p>Owner Panel</p>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">🏠 Dashboard</a></li>
            <li><a href="pages/users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">👥 Users Management</a></li>
            <li><a href="pages/resellers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'resellers.php' ? 'active' : ''; ?>">🤝 Resellers Management</a></li>
            <li><a href="pages/settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">⚙️ Settings</a></li>
            <li><a href="login.php?logout=1" onclick="return confirm('Are you sure you want to logout?')">🚪 Logout</a></li>
        </ul>
    </nav>
</div>