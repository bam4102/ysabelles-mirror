<?php

// Sanitize and validate inputs
$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT) ?: date('n');
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: date('Y');

// Get transaction filter type
$transactionFilter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_STRING) ?: 'all';
$transactionFilter = in_array($transactionFilter, ['all', 'pickup', 'return']) ? $transactionFilter : 'all';

// Validate inputs
$month = ($month < 1 || $month > 12) ? date('n') : $month;
$year = ($year < 1970 || $year > 2100) ? date('Y') : $year;

// Get calendar navigation
$nav = getCalendarNavigation($month, $year);

// Calculate calendar parameters
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDayOfWeek = date('w', $firstDay);
$totalCells = $daysInMonth + $startDayOfWeek;
$rows = ceil($totalCells / 7);

// Get custom date range from POST/GET
$customStart = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_STRING);
$customEnd = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_STRING);

// Function to get all dates between two dates
function getDatesFromRange($start, $end) {
    $dates = [];
    $period = new DatePeriod(
        new DateTime($start),
        new DateInterval('P1D'),
        (new DateTime($end))->modify('+1 day')
    );
    
    foreach ($period as $date) {
        $dates[] = $date;
    }
    return $dates;
}

// Calculate dates for display
if ($customStart && $customEnd) {
    $startDate = date('Y-m-d', strtotime($customStart));
    $endDate = date('Y-m-d', strtotime($customEnd));
    
    // Calculate the start of the week containing the start date
    $displayStart = new DateTime($startDate);
    $displayStart->modify('last sunday'); // Go to the start of the week
    
    // Calculate the end of the week containing the end date
    $displayEnd = new DateTime($endDate);
    $displayEnd->modify('next saturday'); // Go to the end of the week
    
    // Calculate number of weeks
    $interval = $displayStart->diff($displayEnd);
    $rows = ceil(($interval->days + 1) / 7);
    
    $month = (int)date('n', strtotime($startDate));
    $year = (int)date('Y', strtotime($startDate));
} else {
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
}

// Save the effective date range in session for export
$_SESSION['calendar_export'] = [
    'start_date' => $startDate,
    'end_date' => $endDate,
    'type' => $customStart && $customEnd ? 'range' : 'month',
    'filter' => $transactionFilter
];

// Single database call to get all transaction data
$transactions = getTransactionsForDateRange($pdo, $startDate, $endDate);
$transactionsByDate = organizeTransactionsByDate($transactions, $pdo);

// Month names array moved to a constant
const MONTHS = [
    1 => "Jan",
    2 => "Feb",
    3 => "Mar",
    4 => "Apr",
    5 => "May",
    6 => "Jun",
    7 => "Jul",
    8 => "Aug",
    9 => "Sep",
    10 => "Oct",
    11 => "Nov",
    12 => "Dec"
];