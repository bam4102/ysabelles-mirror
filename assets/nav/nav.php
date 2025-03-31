<?php
// Get the current page filename
$currentPage = basename($_SERVER['PHP_SELF']);

// Get user position from session
$userPosition = isset($_SESSION['user']['positionEmployee']) ? strtoupper($_SESSION['user']['positionEmployee']) : '';

// Helper function to generate nav link attributes
function getNavLinkAttributes($page)
{
    global $currentPage;
    $isActive = $currentPage === $page;
    return [
        'class' => 'nav-link ' . ($isActive ? 'active' : ''),
        'aria-current' => $isActive ? 'page' : null,
        'title' => ucfirst(str_replace('_', ' ', str_replace('.php', '', $page)))
    ];
}
?>
<!-- Link to external CSS -->
<link rel="stylesheet" href="./assets/css/sidebar.css">

<!-- Floating toggle button -->
<button class="floating-toggle" id="sidebar-toggle-btn" title="Toggle Navigation">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar-container">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span class="sidebar-title"></span>
            <img src="./assets/img/ybs-logo.png" alt="YBS Logo" class="sidebar-logo">
        </div>
        
        <div class="sidebar-user">
            <span class="sidebar-username"><?php echo htmlspecialchars($currentName); ?></span>
            <div class="sidebar-datetime" id="datetime"></div>
        </div>
        
        <ul class="sidebar-nav">
            <?php if ($userPosition === 'INVENTORY'): ?>
                <li class="sidebar-item">
                    <a class="sidebar-link <?= getNavLinkAttributes('products.php')['class'] ?>"
                        <?= getNavLinkAttributes('products.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                        href="products.php"
                        title="<?= getNavLinkAttributes('products.php')['title'] ?>">
                        <i class="fas fa-box"></i> <span class="sidebar-text">Products</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link <?= getNavLinkAttributes('utilities_section.php')['class'] ?>"
                        <?= getNavLinkAttributes('utilities_section.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                        href="utilities_section.php"
                        title="<?= getNavLinkAttributes('utilities_section.php')['title'] ?>">
                        <i class="fas fa-wrench"></i> <span class="sidebar-text">Utilities</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link <?= getNavLinkAttributes('release.php')['class'] ?>"
                        <?= getNavLinkAttributes('release.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                        href="release.php"
                        title="<?= getNavLinkAttributes('release.php')['title'] ?>">
                        <i class="fas fa-shipping-fast"></i> <span class="sidebar-text">Release</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link <?= getNavLinkAttributes('return.php')['class'] ?>"
                        <?= getNavLinkAttributes('return.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                        href="return.php"
                        title="<?= getNavLinkAttributes('return.php')['title'] ?>">
                        <i class="fas fa-undo"></i> <span class="sidebar-text">Return</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link <?= getNavLinkAttributes('transmittal.php')['class'] ?>"
                        <?= getNavLinkAttributes('transmittal.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                        href="transmittal.php"
                        title="<?= getNavLinkAttributes('transmittal.php')['title'] ?>">
                        <i class="fas fa-share-square"></i> <span class="sidebar-text">Transmittal</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link <?= getNavLinkAttributes('branch_requests.php')['class'] ?>"
                        <?= getNavLinkAttributes('branch_requests.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                        href="branch_requests.php"
                        title="<?= getNavLinkAttributes('branch_requests.php')['title'] ?>">
                        <i class="fas fa-handshake"></i> <span class="sidebar-text">Request</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link <?= getNavLinkAttributes('product_history.php')['class'] ?>"
                        <?= getNavLinkAttributes('product_history.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                        href="product_history.php"
                        title="<?= getNavLinkAttributes('product_history.php')['title'] ?>">
                        <i class="fas fa-history"></i> <span class="sidebar-text">Product History</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link <?= getNavLinkAttributes('calendar.php')['class'] ?>"
                        <?= getNavLinkAttributes('calendar.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                        href="calendar.php"
                        title="<?= getNavLinkAttributes('calendar.php')['title'] ?>">
                        <i class="fas fa-calendar"></i> <span class="sidebar-text">Calendar</span>
                    </a>
                </li>

            <?php elseif ($userPosition === 'SALES'): ?>
                <li class="sidebar-item">
                    <a class="sidebar-link <?= getNavLinkAttributes('calendar.php')['class'] ?>"
                        <?= getNavLinkAttributes('calendar.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                        href="calendar.php"
                        title="<?= getNavLinkAttributes('calendar.php')['title'] ?>">
                        <i class="fas fa-calendar"></i> <span class="sidebar-text">Calendar</span>
                    </a>
                </li>
            <?php else: ?>
                <?php if ($userPosition !== 'CASHIER'): ?>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?= getNavLinkAttributes('products.php')['class'] ?>"
                            <?= getNavLinkAttributes('products.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                            href="products.php"
                            title="<?= getNavLinkAttributes('products.php')['title'] ?>">
                            <i class="fas fa-box"></i> <span class="sidebar-text">Products List</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?= getNavLinkAttributes('utilities_section.php')['class'] ?>"
                            <?= getNavLinkAttributes('utilities_section.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                            href="utilities_section.php"
                            title="<?= getNavLinkAttributes('utilities_section.php')['title'] ?>">
                            <i class="fas fa-wrench"></i> <span class="sidebar-text">Utilities</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($userPosition === 'ADMIN' || $userPosition === 'SUPERADMIN' || $userPosition === 'SALES' || $userPosition === 'CASHIER'): ?>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?= getNavLinkAttributes('transactions2.php')['class'] ?>"
                            <?= getNavLinkAttributes('transactions2.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                            href="transactions2.php"
                            title="<?= getNavLinkAttributes('transactions2.php')['title'] ?>">
                            <i class="fas fa-money-bill-wave"></i> <span class="sidebar-text">Transactions</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($userPosition !== 'CASHIER'): ?>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?= getNavLinkAttributes('release.php')['class'] ?>"
                            <?= getNavLinkAttributes('release.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                            href="release.php"
                            title="<?= getNavLinkAttributes('release.php')['title'] ?>">
                            <i class="fas fa-shipping-fast"></i> <span class="sidebar-text">Release</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?= getNavLinkAttributes('return.php')['class'] ?>"
                            <?= getNavLinkAttributes('return.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                            href="return.php"
                            title="<?= getNavLinkAttributes('return.php')['title'] ?>">
                            <i class="fas fa-undo"></i> <span class="sidebar-text">Return</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?= getNavLinkAttributes('transmittal.php')['class'] ?>"
                            <?= getNavLinkAttributes('transmittal.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                            href="transmittal.php"
                            title="<?= getNavLinkAttributes('transmittal.php')['title'] ?>">
                            <i class="fas fa-share-square"></i> <span class="sidebar-text">Transmittal</span>
                        </a>
                    </li>
                    <?php if ($userPosition === 'ADMIN' || $userPosition === 'SUPERADMIN'): ?>
                        <li class="sidebar-item">
                            <a class="sidebar-link <?= getNavLinkAttributes('branch_requests.php')['class'] ?>"
                                <?= getNavLinkAttributes('branch_requests.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                                href="branch_requests.php"
                                title="<?= getNavLinkAttributes('branch_requests.php')['title'] ?>">
                                <i class="fas fa-handshake"></i> <span class="sidebar-text">Request</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($userPosition === 'ADMIN' || $userPosition === 'SUPERADMIN' || $userPosition === 'CASHIER' || $userPosition === 'SALES'): ?>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?= getNavLinkAttributes('calendar.php')['class'] ?>"
                            <?= getNavLinkAttributes('calendar.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                            href="calendar.php"
                            title="<?= getNavLinkAttributes('calendar.php')['title'] ?>">
                            <i class="fas fa-calendar"></i> <span class="sidebar-text">Calendar</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($userPosition !== 'CASHIER'): ?>
                    <li class="sidebar-item sidebar-dropdown">
                        <a class="sidebar-link dropdown-toggle" href="#" id="reportsDropdown">
                            <i class="fas fa-chart-bar"></i> <span class="sidebar-text">Reports</span>
                        </a>
                        <ul class="sidebar-dropdown-menu" id="reportsMenu">
                            <li><a class="sidebar-dropdown-item" href="product_history.php"><i class="fas fa-history"></i> Product History</a></li>
                            <li><a class="sidebar-dropdown-item" href="reports.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($userPosition === 'ADMIN' || $userPosition === 'SUPERADMIN'): ?>
                    <li class="sidebar-item">
                        <a class="sidebar-link <?= getNavLinkAttributes('manage_employees.php')['class'] ?>"
                            <?= getNavLinkAttributes('manage_employees.php')['aria-current'] ? 'aria-current="page"' : '' ?>
                            href="manage_employees.php"
                            title="<?= getNavLinkAttributes('manage_employees.php')['title'] ?>">
                            <i class="fas fa-users"></i> <span class="sidebar-text">Employees</span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
        
        <div class="sidebar-footer">
            <a href="./assets/controllers/logout.php" class="btn sidebar-logout" title="Logout">
                <i class="fas fa-sign-out-alt"></i> <span class="sidebar-text">Logout</span>
            </a>
        </div>
    </div>
    
    <div class="main-content" id="main-content">
        <!-- Main content goes here -->
    </div>
</div>

<!-- Link to external JavaScript -->
<script src="./assets/scripts/sidebar.js"></script>