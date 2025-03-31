<?php
session_start();
include 'auth.php';
include './assets/controllers/db.php';
include './assets/controllers/return/return_controller.php';

// Check if the user session exists and is not empty
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: login_page.php");
    exit;
}

// Allow only ADMIN and INVENTORY positions to access this page
$allowedPositions = ['ADMIN', 'INVENTORY', 'SUPERADMIN'];
if (!in_array(strtoupper($_SESSION['user']['positionEmployee']), $allowedPositions)) {
    header("Location: credential_error.php");
    exit;
}

// Get the name of the currently logged-in user from the session.
$currentName = $_SESSION['user']['nameEmployee'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Return Product</title>
    <link rel="icon" type="image/png" href="./assets/img/sitelogo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- jQuery for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link rel="stylesheet" href="./assets/css/global.css">
    <style>
        body {
            background-color: #FEF7F2;
            font-family: 'Segoe UI', sans-serif;
        }

        h1 {
            font-weight: bold;
            padding: 15px 20px;
            background-color: white;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
            background-color: #FC4A49;
            color: white;
        }

        .search-container, .scan-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        #transactionTable {
            cursor: pointer;
        }

        #transactionTable tbody tr:hover {
            background-color: #f5f5f5;
        }

        #scanSection {
            display: none;
        }
        
        .product-header {
            background-color: #FC4A49;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            text-align: center;
        }
    </style>
</head>

<body>
    <?php include './assets/nav/nav.php'; ?>
    <div class="container my-4">
        <h1>Return Product</h1>
        
        <!-- Transaction Search Section -->
        <div id="searchSection" class="search-container">
            <h3>Search Transaction</h3>
            <p class="text-muted">Search for a transaction by client name or transaction ID.</p>
            
            <form id="searchForm" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="searchQuery" name="searchQuery" placeholder="Enter client name or transaction ID" required>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="searchType" name="searchType">
                            <option value="clientName">Client Name</option>
                            <option value="transactionID">Transaction ID</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </form>
            
            <div id="searchResults" class="mt-4"></div>
        </div>
        
        <!-- Product Scanning Section (Hidden initially) -->
        <div id="scanSection" class="scan-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Return Products</h3>
                <button id="backToSearch" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Search</button>
            </div>
            
            <div id="transactionInfo" class="mb-4"></div>
            
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Product Scanning</h5>
                </div>
                <div class="card-body">
                    <form id="returnForm" action="return.php" method="POST">
                        <input type="hidden" id="selectedTransactionID" name="transactionID">
                        <div class="mb-3">
                            <label for="codeProduct" class="form-label">
                                <strong>Scan Product Code</strong>
                                <span class="text-muted small ms-2">Scan products one at a time</span>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-lg" id="codeProduct" name="codeProduct" placeholder="Scan or enter product code" required autofocus>
                                <button type="submit" class="btn btn-primary" style="min-width: 100px;">Process</button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Results display area -->
                    <div id="resultContainer" class="mt-4" style="overflow: hidden;"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap Bundle JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/scripts/return.js"></script>
</body>

</html>