<?php
session_start();

// Check if the user session exists and is not empty
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: login_page.php");
    exit;
}

// Allow access to both ADMIN and SUPERADMIN users
$position = strtoupper($_SESSION['user']['positionEmployee']);
if (!isset($_SESSION['user']) || ($position !== 'ADMIN' && $position !== 'SUPERADMIN')) {
    header("Location: credential_error.php");
    exit;
}

// Get the name of the currently logged-in user from the session.
$currentName = $_SESSION['user']['nameEmployee'];
$currentLocation = $_SESSION['user']['locationEmployee'];

include 'auth.php';
//Connect to the database.
include './assets/controllers/db.php';

// Helper function for position badge classes
function getPositionBadgeClass($position) {
    switch (strtoupper($position)) {
        case 'SUPERADMIN':
            return 'bg-dark';
        case 'ADMIN':
            return 'bg-danger';
        case 'INVENTORY':
            return 'bg-warning text-dark';
        case 'SALES':
            return 'bg-primary';
        case 'CASHIER':
            return 'bg-success';
        case 'COMPUTER':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

// Helper function for employed status badge
function getEmployedBadgeClass($employed) {
    return $employed ? 'bg-success' : 'bg-danger';
}

// Retrieve employee records based on position
// ADMIN can only see employees from their own location, SUPERADMIN can see all
if ($position === 'ADMIN') {
    $stmt = $pdo->prepare("SELECT * FROM employee WHERE locationEmployee = ?");
    $stmt->execute([$currentLocation]);
} else {
    // For SUPERADMIN, retrieve all employee records
    $stmt = $pdo->query("SELECT * FROM employee");
}
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <!-- Scaling on mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees</title>
    <link rel="icon" type="image/png" href="./assets/img/sitelogo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./assets/css/manage_employees.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="./assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body data-position="<?php echo htmlspecialchars($position); ?>" data-location="<?php echo htmlspecialchars($currentLocation); ?>">
    <!-- Include Navigation -->
    <?php include 'assets/nav/nav.php'; ?>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4 heading-container">
            <h1 class="mb-0 fw-bold">Manage Employees</h1>
            <div class="d-flex gap-2">
                <?php if ($position === 'SUPERADMIN'): ?>
                    <select class="form-select" id="locationFilter" style="width: auto;">
                        <option value="">All Locations</option>
                        <option value="BACOLOD CITY">BACOLOD CITY</option>
                        <option value="DUMAGUETE CITY">DUMAGUETE CITY</option>
                        <option value="ILOILO CITY">ILOILO CITY</option>
                        <option value="SAN CARLOS CITY">SAN CARLOS CITY</option>
                        <option value="CEBU CITY">CEBU CITY</option>
                    </select>
                <?php endif; ?>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Account
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="employeeTable">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="name">Name <i class="bi bi-arrow-down-up sort-icon"></i></th>
                        <th class="sortable" data-sort="start-date">Start Date <i class="bi bi-arrow-down-up sort-icon"></i></th>
                        <th class="sortable" data-sort="end-date">End Date <i class="bi bi-arrow-down-up sort-icon"></i></th>
                        <th class="sortable" data-sort="location">Location <i class="bi bi-arrow-down-up sort-icon"></i></th>
                        <th class="sortable" data-sort="position">Position <i class="bi bi-arrow-down-up sort-icon"></i></th>
                        <th class="sortable" data-sort="employed">Status <i class="bi bi-arrow-down-up sort-icon"></i></th>
                        <th class="sortable" data-sort="last-login">Last Login <i class="bi bi-arrow-down-up sort-icon"></i></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr id="employee-<?php echo $employee['employeeID']; ?>" 
                            data-id="<?php echo htmlspecialchars($employee['employeeID']); ?>"
                            data-name="<?php echo htmlspecialchars($employee['nameEmployee']); ?>"
                            data-username="<?php echo htmlspecialchars($employee['usernameEmployee']); ?>"
                            data-start-date="<?php echo htmlspecialchars($employee['dateStart']); ?>"
                            data-end-date="<?php echo htmlspecialchars($employee['dateEnd'] ?? ''); ?>"
                            data-location="<?php echo htmlspecialchars($employee['locationEmployee']); ?>"
                            data-position="<?php echo htmlspecialchars($employee['positionEmployee']); ?>"
                            data-employed="<?php echo $employee['employedEmployee']; ?>"
                            data-last-login="<?php echo htmlspecialchars($employee['lastLogin'] ?? ''); ?>">
                            <td><?php echo htmlspecialchars($employee['nameEmployee']); ?></td>
                            <td><?php echo htmlspecialchars($employee['dateStart']); ?></td>
                            <td><?php echo htmlspecialchars($employee['dateEnd']); ?></td>
                            <td><?php echo htmlspecialchars($employee['locationEmployee']); ?></td>
                            <td>
                                <span class="badge <?= getPositionBadgeClass($employee['positionEmployee']) ?>">
                                    <?php echo htmlspecialchars($employee['positionEmployee']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= getEmployedBadgeClass($employee['employedEmployee']) ?>">
                                    <?php echo $employee['employedEmployee'] ? 'ACTIVE' : 'INACTIVE'; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if (!empty($employee['lastLogin'])) {
                                    $lastLogin = new DateTime($employee['lastLogin']);
                                    echo $lastLogin->format('M d, Y h:i A');
                                } else {
                                    echo '<span class="text-muted">Never</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="openEditModal(<?php echo $employee['employeeID']; ?>)">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteEmployee(<?php echo $employee['employeeID']; ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="editEmployeeForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Employee</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_employeeID" name="employeeID">
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="edit_nameEmployee" class="form-label">Name:</label>
                                    <input type="text" class="form-control" id="edit_nameEmployee" name="nameEmployee" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="edit_usernameEmployee" class="form-label">Username:</label>
                                    <input type="text" class="form-control" id="edit_usernameEmployee" name="usernameEmployee" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="edit_passwordEmployee" class="form-label">Password:</label>
                                    <input type="password" class="form-control" id="edit_passwordEmployee" name="passwordEmployee" placeholder="Enter new password">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="edit_dateStart" class="form-label">Start Date:</label>
                                    <input type="date" class="form-control" id="edit_dateStart" name="dateStart" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="edit_dateEnd" class="form-label">End Date (optional):</label>
                                    <input type="date" class="form-control" id="edit_dateEnd" name="dateEnd">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="edit_positionEmployee" class="form-label">Position:</label>
                                    <select class="form-select" id="edit_positionEmployee" name="positionEmployee" required>
                                        <option value="">--Select Position--</option>
                                        <?php if ($position === 'SUPERADMIN'): ?>
                                            <option value="SUPERADMIN">Super Admin</option>
                                            <option value="ADMIN">Admin</option>
                                        <?php endif; ?>
                                        <option value="CASHIER">Cashier</option>
                                        <option value="COMPUTER">Computer</option>
                                        <option value="INVENTORY">Inventory</option>
                                        <option value="SALES">Sales</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="edit_locationEmployee" class="form-label">Location:</label>
                                    <select class="form-select" id="edit_locationEmployee" name="locationEmployee" required <?php echo ($position === 'ADMIN') ? 'disabled' : ''; ?>>
                                        <?php if ($position === 'ADMIN'): ?>
                                            <option value="<?php echo htmlspecialchars($currentLocation); ?>" selected><?php echo htmlspecialchars($currentLocation); ?></option>
                                        <?php else: ?>
                                            <option value="">--Select Location--</option>
                                            <option value="BACOLOD CITY">BACOLOD CITY</option>
                                            <option value="DUMAGUETE CITY">DUMAGUETE CITY</option>
                                            <option value="ILOILO CITY">ILOILO CITY</option>
                                            <option value="SAN CARLOS CITY">SAN CARLOS CITY</option>
                                            <option value="CEBU CITY">CEBU CITY</option>
                                        <?php endif; ?>
                                    </select>
                                    <?php if ($position === 'ADMIN'): ?>
                                        <!-- Hidden field to ensure the location is submitted even with disabled select -->
                                        <input type="hidden" name="locationEmployee" value="<?php echo htmlspecialchars($currentLocation); ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="edit_employedEmployee" name="employedEmployee">
                            <label class="form-check-label" for="edit_employedEmployee">Employed</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="addEmployeeForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">Add Employee Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="add_nameEmployee" class="form-label">Name:</label>
                                    <input type="text" class="form-control" id="add_nameEmployee" name="nameEmployee" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="add_usernameEmployee" class="form-label">Username:</label>
                                    <input type="text" class="form-control" id="add_usernameEmployee" name="usernameEmployee" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="add_passwordEmployee" class="form-label">Password:</label>
                                    <input type="password" class="form-control" id="add_passwordEmployee" name="passwordEmployee" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="add_dateStart" class="form-label">Start Date:</label>
                                    <input type="date" class="form-control" id="add_dateStart" name="dateStart" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="add_dateEnd" class="form-label">End Date (optional):</label>
                                    <input type="date" class="form-control" id="add_dateEnd" name="dateEnd">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="add_positionEmployee" class="form-label">Position:</label>
                                    <select class="form-select" id="add_positionEmployee" name="positionEmployee" required>
                                        <option value="">--Select Position--</option>
                                        <?php if ($position === 'SUPERADMIN'): ?>
                                            <option value="SUPERADMIN">Super Admin</option>
                                            <option value="ADMIN">Admin</option>
                                        <?php endif; ?>
                                        <option value="CASHIER">Cashier</option>
                                        <option value="COMPUTER">Computer</option>
                                        <option value="INVENTORY">Inventory</option>
                                        <option value="SALES">Sales</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="add_locationEmployee" class="form-label">Location:</label>
                                    <select class="form-select" id="add_locationEmployee" name="locationEmployee" required <?php echo ($position === 'ADMIN') ? 'disabled' : ''; ?>>
                                        <?php if ($position === 'ADMIN'): ?>
                                            <option value="<?php echo htmlspecialchars($currentLocation); ?>" selected><?php echo htmlspecialchars($currentLocation); ?></option>
                                        <?php else: ?>
                                            <option value="">--Select Location--</option>
                                            <option value="BACOLOD CITY">BACOLOD CITY</option>
                                            <option value="DUMAGUETE CITY">DUMAGUETE CITY</option>
                                            <option value="ILOILO CITY">ILOILO CITY</option>
                                            <option value="SAN CARLOS CITY">SAN CARLOS CITY</option>
                                            <option value="CEBU CITY">CEBU CITY</option>
                                        <?php endif; ?>
                                    </select>
                                    <?php if ($position === 'ADMIN'): ?>
                                        <!-- Hidden field to ensure the location is submitted even with disabled select -->
                                        <input type="hidden" name="locationEmployee" value="<?php echo htmlspecialchars($currentLocation); ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-switch">
                            <input class="form-check-input" type="checkbox" id="add_employedEmployee" name="employedEmployee">
                            <label class="form-check-label" for="add_employedEmployee">Employed</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <!-- On success, the page will redirect back to manage_employees.php -->
                        <button type="submit" class="btn btn-primary">Add Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/scripts/manage_employees.js"> </script>
</body>

</html>