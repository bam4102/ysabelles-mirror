<?php
session_start();
include 'auth.php';
// Security checks
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: login_page.php");
    exit;
}

$allowedPositions = ['ADMIN', 'CASHIER', 'INVENTORY', 'SALES', 'SUPERADMIN'];
if (!in_array(strtoupper($_SESSION['user']['positionEmployee']), $allowedPositions)) {
    header("Location: credential_error.php");
    exit;
}

require_once './assets/controllers/db.php';
require_once './assets/controllers/calendar/calendar_functions.php';
require_once './assets/controllers/calendar/calendar_controller.php';

// Get current user name
$currentName = htmlspecialchars($_SESSION['user']['nameEmployee']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Calendar - <?= MONTHS[$month] . " " . $year ?></title>
    <link rel="icon" type="image/png" href="./assets/img/sitelogo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/calendar.css">
    <link rel="stylesheet" href="./assets/css/global.css">
</head>

<body>
    <?php include './assets/nav/nav.php'; ?>
    <div class="container my-4">
        <div class="mb-4 calendar-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1>
                    <?php if ($customStart && $customEnd): ?>
                        Transactions from <?= date('F j, Y', strtotime($customStart)) ?> to <?= date('F j, Y', strtotime($customEnd)) ?>
                    <?php else: ?>
                        <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?> Transactions
                    <?php endif; ?>
                </h1>
                <ul class="nav nav-pills" id="calendarTabs" role="tablist">
                    <li class="utility-nav-item" role="presentation">
                        <button class="utility-nav-link <?= !$customStart ? 'active' : '' ?>" id="month-tab" data-bs-toggle="pill" data-bs-target="#month-view" type="button" role="tab" aria-controls="month-view" aria-selected="<?= !$customStart ? 'true' : 'false' ?>">
                            Month
                        </button>
                    </li>
                    <li class="utility-nav-item" role="presentation">
                        <button class="utility-nav-link <?= $customStart ? 'active' : '' ?>" id="range-tab" data-bs-toggle="pill" data-bs-target="#range-view" type="button" role="tab" aria-controls="range-view" aria-selected="<?= $customStart ? 'true' : 'false' ?>">
                            Date Range
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Navigation and Controls -->
        <div class="card mb-3">
            <div class="filters-header">
                <h5>Filters</h5>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Date Range View -->
                    <div class="tab-pane fade <?= $customStart ? 'show active' : '' ?>" id="range-view">
                        <div class="row g-3">
                            <!-- Date Range Controls -->
                            <div class="col-lg-7">
                                <form class="d-flex flex-column gap-2" id="dateRangeForm">
                                    <div class="d-flex gap-2">
                                        <div class="input-group">
                                            <span class="input-group-text">From</span>
                                            <input type="date" name="start_date" class="form-control" value="" required>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text">To</span>
                                            <input type="date" name="end_date" class="form-control" value="" required>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md">
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-custom flex-grow-1" onclick="resetCalendar()">
                                                    <i class="fas fa-sync-alt me-1"></i> Reset
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md">
                                            <div class="d-flex">
                                                <button type="submit" class="btn btn-custom flex-grow-1">
                                                    <i class="fas fa-calendar-check me-1"></i> View
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md">
                                            <button type="button" id="exportBtn" class="btn btn-custom w-100" onclick="exportTransactions()">
                                                <i class="fas fa-file-csv me-1"></i> Export
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Filter Options -->
                            <div class="col-lg-5">
                                <div class="d-flex flex-column h-100">
                                    <label class="form-label">Filter Transactions</label>
                                    <div class="btn-group" role="group">
                                        <a href="#" class="btn <?= $transactionFilter === 'all' ? 'btn-custom' : 'btn-outline-secondary' ?>" data-filter="all">All</a>
                                        <a href="#" class="btn <?= $transactionFilter === 'pickup' ? 'btn-custom' : 'btn-outline-secondary' ?>" data-filter="pickup">Pickup</a>
                                        <a href="#" class="btn <?= $transactionFilter === 'return' ? 'btn-custom' : 'btn-outline-secondary' ?>" data-filter="return">Return</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Month View -->
                    <div class="tab-pane fade <?= !$customStart ? 'show active' : '' ?>" id="month-view">
                        <div class="row g-3">
                            <!-- Month/Year Selection -->
                            <div class="col-lg-7">
                                <form id="monthYearForm" class="d-flex flex-column gap-2">
                                    <input type="hidden" name="filter" value="<?= $transactionFilter ?>">
                                    <div class="row g-2">
                                        <div class="col-md">
                                            <div class="d-flex">
                                                <button type="button" class="btn btn-custom flex-grow-1" onclick="resetCalendar()">
                                                    <i class="fas fa-sync-alt me-1"></i> Reset
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md">
                                            <div class="d-flex">
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                    <input type="month" name="month_year" class="form-control"
                                                        value="<?= sprintf('%04d-%02d', $year, $month) ?>"
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md">
                                            <button type="button" id="exportMonthBtn" class="btn btn-custom w-100" onclick="exportTransactions()">
                                                <i class="fas fa-file-csv me-1"></i> Export
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md">
                                            <div class="d-flex">
                                                <button type="button" class="btn btn-custom flex-grow-1" onclick="navigateCalendar('prev')">
                                                    <i class="fas fa-chevron-left me-1"></i> Previous
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md">
                                            <div class="d-flex">
                                                <button type="submit" class="btn btn-custom flex-grow-1">
                                                    <i class="fas fa-calendar-check me-1"></i> View
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md">
                                            <div class="d-flex">
                                                <button type="button" class="btn btn-custom flex-grow-1" onclick="navigateCalendar('next')">
                                                    Next <i class="fas fa-chevron-right ms-1"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Filter Options -->
                            <div class="col-lg-5">
                                <div class="d-flex flex-column h-100">
                                    <label class="form-label">Filter Transactions</label>
                                    <div class="btn-group" role="group">
                                        <a href="#" class="btn <?= $transactionFilter === 'all' ? 'btn-custom' : '' ?>" data-filter="all">All</a>
                                        <a href="#" class="btn <?= $transactionFilter === 'pickup' ? 'btn-custom' : '' ?>" data-filter="pickup">Pickup</a>
                                        <a href="#" class="btn <?= $transactionFilter === 'return' ? 'btn-custom' : '' ?>" data-filter="return">Return</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Table -->
        <table class="table calendar-table">
            <thead>
                <tr>
                    <th>Sun</th>
                    <th>Mon</th>
                    <th>Tue</th>
                    <th>Wed</th>
                    <th>Thu</th>
                    <th>Fri</th>
                    <th>Sat</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($customStart && $customEnd) {
                    // Custom date range view
                    $currentDate = new DateTime($startDate);
                    $currentDate->modify('-' . $startDayOfWeek . ' days'); // Go to start of week

                    for ($row = 0; $row < $rows; $row++) {
                        echo "<tr>";
                        for ($col = 0; $col < 7; $col++) {
                            $date = $currentDate->format('Y-m-d');
                            $inRange = $date >= $startDate && $date <= $endDate;
                            $cellClass = $inRange ? '' : 'text-muted';
                            if ($date == date('Y-m-d')) {
                                $cellClass .= ' today';
                            }

                            echo "<td class='$cellClass'>";
                            echo "<strong>" . $currentDate->format('j') . "</strong>";
                            echo "<small class='d-block text-muted'>" . $currentDate->format('M') . "</small>";

                            if ($inRange && isset($transactionsByDate[$date])) {
                                echo '<div class="transaction-info">';
                                if (($transactionFilter === 'all' || $transactionFilter === 'pickup') && isset($transactionsByDate[$date]['pickup'])) {
                                    foreach ($transactionsByDate[$date]['pickup'] as $trans) {
                                        echo "<div class='transaction-pickup'><a href='#' class='transaction-detail' 
                                            data-transactionid='" . htmlspecialchars($trans['transactionID']) . "' data-type='pickup'>
                                            <span class='pickup-label'>P:</span> ID: " . htmlspecialchars($trans['transactionID']) .
                                            " (" . htmlspecialchars($trans['clientName']) . ")</a></div>";
                                    }
                                }
                                if (($transactionFilter === 'all' || $transactionFilter === 'return') && isset($transactionsByDate[$date]['return'])) {
                                    foreach ($transactionsByDate[$date]['return'] as $trans) {
                                        echo "<div class='transaction-return'><a href='#' class='transaction-detail' 
                                            data-transactionid='" . htmlspecialchars($trans['transactionID']) . "' data-type='return'>
                                            <span class='return-label'>R:</span> ID: " . htmlspecialchars($trans['transactionID']) .
                                            " (" . htmlspecialchars($trans['clientName']) . ")</a></div>";
                                    }
                                }
                                echo '</div>';
                            }
                            echo "</td>";
                            $currentDate->modify('+1 day');
                        }
                        echo "</tr>";
                    }
                } else {
                    // Full month view: loop through all rows.
                    $currentDay = 1;
                    for ($row = 0; $row < $rows; $row++) {
                        echo "<tr>";
                        for ($col = 0; $col < 7; $col++) {
                            $cellIndex = $row * 7 + $col;
                            if ($cellIndex < $startDayOfWeek || $currentDay > $daysInMonth) {
                                echo "<td></td>";
                            } else {
                                $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
                                $cellClass = ($currentDay == date('j') && $month == date('n') && $year == date('Y')) ? "today" : "";
                                echo "<td class='$cellClass'><strong>$currentDay</strong>";
                                if (isset($transactionsByDate[$currentDate])) {
                                    echo '<div class="transaction-info">';
                                    if (($transactionFilter === 'all' || $transactionFilter === 'pickup') && isset($transactionsByDate[$currentDate]['pickup'])) {
                                        foreach ($transactionsByDate[$currentDate]['pickup'] as $trans) {
                                            echo "<div class='transaction-pickup'><a href='#' class='transaction-detail' data-transactionid='" . htmlspecialchars($trans['transactionID']) . "' data-type='pickup'>
                                                    <span class='pickup-label'>P:</span> ID: " . htmlspecialchars($trans['transactionID']) . " (" . htmlspecialchars($trans['clientName']) . ")
                                                  </a></div>";
                                        }
                                    }
                                    if (($transactionFilter === 'all' || $transactionFilter === 'return') && isset($transactionsByDate[$currentDate]['return'])) {
                                        foreach ($transactionsByDate[$currentDate]['return'] as $trans) {
                                            echo "<div class='transaction-return'><a href='#' class='transaction-detail' data-transactionid='" . htmlspecialchars($trans['transactionID']) . "' data-type='return'>
                                                    <span class='return-label'>R:</span> ID: " . htmlspecialchars($trans['transactionID']) . " (" . htmlspecialchars($trans['clientName']) . ")
                                                  </a></div>";
                                        }
                                    }
                                    echo '</div>';
                                }
                                echo "</td>";
                                $currentDay++;
                            }
                        }
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
        <p class="text-center">
            <?php if ($transactionFilter === 'all'): ?>
                Click on a <span style="color: #4CAF50">P</span> or <span style="color: #FC4A49">R</span> to view transaction details.
            <?php elseif ($transactionFilter === 'pickup'): ?>
                Viewing <span style="color: #4CAF50">PICKUP</span> transactions only. Click to view details.
            <?php elseif ($transactionFilter === 'return'): ?>
                Viewing <span style="color: #FC4A49">RETURN</span> transactions only. Click to view details.
            <?php endif; ?>
        </p>
    </div>

    <!-- Transaction Detail Modal -->
    <div class="modal fade" id="transactionDetailModal" tabindex="-1" aria-labelledby="transactionDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transactionDetailModalLabel">Transaction Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="transactionDetailContent">
                    <div class="text-center p-4">
                        <div class="spinner-border text-danger" role="status"></div>
                        <p class="mt-2">Loading transaction details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Detail Modal -->
    <div class="modal fade" id="productDetailModal" tabindex="-1" aria-labelledby="productDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productDetailModalLabel">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="productDetailContent">
                    <div class="text-center p-4">
                        <div class="spinner-border text-danger" role="status"></div>
                        <p class="mt-2">Loading product details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Initialize page with URL parameters -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const filter = urlParams.get('filter');
            const month = urlParams.get('month');
            const year = urlParams.get('year');
            const startDate = urlParams.get('start_date');
            const endDate = urlParams.get('end_date');

            // Set filter buttons
            if (filter) {
                document.querySelectorAll('.btn-group .btn').forEach(btn => {
                    btn.classList.remove('btn-custom');
                    if (btn.dataset.filter === filter) {
                        btn.classList.add('btn-custom');
                    }
                });
            }

            // Set month/year or date range values
            if (month && year) {
                // Select the month-year tab
                document.querySelector('#month-tab').click();

                // Set month-year input value
                const monthInput = document.querySelector('input[name="month_year"]');
                if (monthInput) {
                    // Format: YYYY-MM with padding
                    monthInput.value = `${year}-${month.toString().padStart(2, '0')}`;
                }
            } else if (startDate && endDate) {
                // Select the date range tab
                document.querySelector('#range-tab').click();

                // Set date inputs
                const startInput = document.querySelector('input[name="start_date"]');
                const endInput = document.querySelector('input[name="end_date"]');
                if (startInput) startInput.value = startDate;
                if (endInput) endInput.value = endDate;
            } else {
                // Default to current month
                const today = new Date();
                const currentMonth = (today.getMonth() + 1).toString().padStart(2, '0');
                const currentYear = today.getFullYear();

                const monthInput = document.querySelector('input[name="month_year"]');
                if (monthInput) {
                    monthInput.value = `${currentYear}-${currentMonth}`;
                }
            }

            // Update calendar
            updateCalendar();
        });
    </script>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/scripts/calendar.js"></script>
</body>

</html>