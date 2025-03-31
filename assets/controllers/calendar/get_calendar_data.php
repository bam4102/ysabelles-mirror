<?php
session_start();
require_once '../db.php';
require_once 'calendar_functions.php';

// Ensure no output before JSON response
ob_clean();
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        throw new Exception('Unauthorized access');
    }

    // Sanitize inputs
    $filter = filter_input(INPUT_POST, 'filter', FILTER_SANITIZE_STRING) ?: 'all';
    $startDate = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $endDate = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);
    $month = filter_input(INPUT_POST, 'month', FILTER_SANITIZE_NUMBER_INT) ?: date('n');
    $year = filter_input(INPUT_POST, 'year', FILTER_SANITIZE_NUMBER_INT) ?: date('Y');
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING) ?: 'month';

    // Validate inputs
    $month = ($month < 1 || $month > 12) ? date('n') : $month;
    $year = ($year < 1970 || $year > 2100) ? date('Y') : $year;
    $filter = in_array($filter, ['all', 'pickup', 'return']) ? $filter : 'all';

    // Get calendar navigation
    $nav = getCalendarNavigation($month, $year);

    // Calculate calendar parameters
    $firstDay = mktime(0, 0, 0, $month, 1, $year);
    $daysInMonth = date('t', $firstDay);
    $startDayOfWeek = date('w', $firstDay);
    $totalCells = $daysInMonth + $startDayOfWeek;
    $rows = ceil($totalCells / 7);

    // Calculate dates for display
    if ($startDate && $endDate) {
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        
        // Calculate the start of the week containing the start date
        $displayStart = new DateTime($startDate);
        $displayStart->modify('last sunday');
        
        // Calculate the end of the week containing the end date
        $displayEnd = new DateTime($endDate);
        $displayEnd->modify('next saturday');
        
        // Calculate number of weeks
        $interval = $displayStart->diff($displayEnd);
        $rows = ceil(($interval->days + 1) / 7);
    } else {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
    }

    // Get transaction data
    $transactions = getTransactionsForDateRange($pdo, $startDate, $endDate);
    $transactionsByDate = organizeTransactionsByDate($transactions, $pdo);

    // Generate calendar HTML
    ob_start();
    if ($startDate && $endDate) {
        // Custom date range view
        $currentDate = new DateTime($startDate);
        $currentDate->modify('-' . $startDayOfWeek . ' days');

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
                    if (($filter === 'all' || $filter === 'pickup') && isset($transactionsByDate[$date]['pickup'])) {
                        foreach ($transactionsByDate[$date]['pickup'] as $trans) {
                            echo "<div class='transaction-pickup'><a href='#' class='transaction-detail' 
                                data-transactionid='" . htmlspecialchars($trans['transactionID']) . "' data-type='pickup'>
                                <span class='pickup-label'>P:</span> ID: " . htmlspecialchars($trans['transactionID']) .
                                " (" . htmlspecialchars($trans['clientName']) . ")</a></div>";
                        }
                    }
                    if (($filter === 'all' || $filter === 'return') && isset($transactionsByDate[$date]['return'])) {
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
        // Month view
        $currentDay = 1;
        for ($row = 0; $row < $rows; $row++) {
            echo "<tr>";
            for ($col = 0; $col < 7; $col++) {
                if ($row === 0 && $col < $startDayOfWeek) {
                    echo "<td></td>";
                    continue;
                }
                if ($currentDay > $daysInMonth) {
                    echo "<td></td>";
                    continue;
                }

                $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
                $cellClass = '';
                if ($currentDate == date('Y-m-d')) {
                    $cellClass = 'today';
                }

                echo "<td class='$cellClass'>";
                echo "<strong>$currentDay</strong>";

                if (isset($transactionsByDate[$currentDate])) {
                    echo '<div class="transaction-info">';
                    if (($filter === 'all' || $filter === 'pickup') && isset($transactionsByDate[$currentDate]['pickup'])) {
                        foreach ($transactionsByDate[$currentDate]['pickup'] as $trans) {
                            echo "<div class='transaction-pickup'><a href='#' class='transaction-detail' data-transactionid='" . htmlspecialchars($trans['transactionID']) . "' data-type='pickup'>
                                    <span class='pickup-label'>P:</span> ID: " . htmlspecialchars($trans['transactionID']) . " (" . htmlspecialchars($trans['clientName']) . ")
                                  </a></div>";
                        }
                    }
                    if (($filter === 'all' || $filter === 'return') && isset($transactionsByDate[$currentDate]['return'])) {
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
            echo "</tr>";
        }
    }
    $calendarHtml = ob_get_clean();

    // Define month names
    $months = [
        1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "May", 6 => "Jun",
        7 => "Jul", 8 => "Aug", 9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dec"
    ];

    // Prepare response
    $response = [
        'success' => true,
        'calendarHtml' => $calendarHtml,
        'title' => $startDate && $endDate 
            ? ($type === 'month' 
              ? date('F Y', strtotime($startDate)) . " Transactions"
              : "Transactions from " . date('F j, Y', strtotime($startDate)) . " to " . date('F j, Y', strtotime($endDate)))
            : date('F Y', mktime(0, 0, 0, $month, 1, $year)) . " Transactions"
    ];

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}