// Initialize Bootstrap modals
var editModalInstance = new bootstrap.Modal(document.getElementById('editModal'));
var addModalInstance = new bootstrap.Modal(document.getElementById('addModal'));

// Cache for employee data
let employeeDataCache = {};

// Helper function to get position badge class
function getPositionBadgeClass(position) {
    position = position.toUpperCase();
    switch (position) {
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

// Helper function to get employed badge class
function getEmployedBadgeClass(employed) {
    return employed ? 'bg-success' : 'bg-danger';
}

// Function to format date and time
function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return '<span class="text-muted">Never</span>';
    const date = new Date(dateTimeStr);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    });
}

// Function to open the Edit Employee modal and populate the fields.
function openEditModal(employeeID) {
    // Get employee data from cache
    const emp = employeeDataCache[employeeID];
    if (emp) {
        populateEditModal(emp);
        editModalInstance.show();
        return;
    }

    // If not in cache, fetch from server
    fetch('./assets/controllers/manage_employees/get_employee.php?employeeID=' + employeeID)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cache the employee data
                employeeDataCache[data.employee.employeeID] = data.employee;
                populateEditModal(data.employee);
                editModalInstance.show();
            } else {
                alert('Error: ' + (data.error || 'Could not fetch employee details.'));
            }
        })
        .catch(error => {
            console.error('Error fetching employee details:', error);
            alert('An error occurred while fetching employee details.');
        });
}

// Function to populate edit modal with employee data
function populateEditModal(emp) {
    document.getElementById('edit_employeeID').value = emp.employeeID;
    document.getElementById('edit_nameEmployee').value = emp.nameEmployee;
    document.getElementById('edit_usernameEmployee').value = emp.usernameEmployee;
    document.getElementById('edit_passwordEmployee').value = '';
    document.getElementById('edit_dateStart').value = emp.dateStart;
    document.getElementById('edit_dateEnd').value = emp.dateEnd || '';
    
    const locationSelect = document.getElementById('edit_locationEmployee');
    if (!locationSelect.disabled) {
        locationSelect.value = emp.locationEmployee;
    }
    
    document.getElementById('edit_positionEmployee').value = emp.positionEmployee;
    document.getElementById('edit_employedEmployee').checked = (emp.employedEmployee == 1);
}

// Function to update a row in the table with new data
function updateTableRow(employeeID, newData) {
    // Update the cache with new data
    employeeDataCache[employeeID] = newData;
    
    const row = document.getElementById("employee-" + employeeID);
    if (row) {
        // Update data attributes
        row.dataset.name = newData.nameEmployee;
        row.dataset.username = newData.usernameEmployee;
        row.dataset.startDate = newData.dateStart;
        row.dataset.endDate = newData.dateEnd || '';
        row.dataset.location = newData.locationEmployee;
        row.dataset.position = newData.positionEmployee;
        row.dataset.employed = newData.employedEmployee;
        row.dataset.lastLogin = newData.lastLogin || '';

        // Update visible cells
        row.cells[0].textContent = newData.nameEmployee;
        row.cells[1].textContent = newData.dateStart;
        row.cells[2].textContent = newData.dateEnd || '';
        row.cells[3].textContent = newData.locationEmployee;
        
        // Update position badge
        row.cells[4].innerHTML = `
            <span class="badge ${getPositionBadgeClass(newData.positionEmployee)}">
                ${newData.positionEmployee}
            </span>`;
        
        // Update employed status badge
        row.cells[5].innerHTML = `
            <span class="badge ${getEmployedBadgeClass(newData.employedEmployee)}">
                ${newData.employedEmployee ? 'ACTIVE' : 'INACTIVE'}
            </span>`;

        // Update last login
        row.cells[6].innerHTML = formatDateTime(newData.lastLogin);
    }
}

// Function to add a new row to the table
function addTableRow(employeeData) {
    // Add to cache
    employeeDataCache[employeeData.employeeID] = employeeData;
    
    const tbody = document.querySelector('#employeeTable tbody');
    const newRow = document.createElement('tr');
    newRow.id = `employee-${employeeData.employeeID}`;
    
    // Set data attributes
    newRow.dataset.id = employeeData.employeeID;
    newRow.dataset.name = employeeData.nameEmployee;
    newRow.dataset.username = employeeData.usernameEmployee;
    newRow.dataset.startDate = employeeData.dateStart;
    newRow.dataset.endDate = employeeData.dateEnd || '';
    newRow.dataset.location = employeeData.locationEmployee;
    newRow.dataset.position = employeeData.positionEmployee;
    newRow.dataset.employed = employeeData.employedEmployee;
    newRow.dataset.lastLogin = employeeData.lastLogin || '';

    newRow.innerHTML = `
        <td>${employeeData.nameEmployee}</td>
        <td>${employeeData.dateStart}</td>
        <td>${employeeData.dateEnd || ''}</td>
        <td>${employeeData.locationEmployee}</td>
        <td>
            <span class="badge ${getPositionBadgeClass(employeeData.positionEmployee)}">
                ${employeeData.positionEmployee}
            </span>
        </td>
        <td>
            <span class="badge ${getEmployedBadgeClass(employeeData.employedEmployee)}">
                ${employeeData.employedEmployee ? 'ACTIVE' : 'INACTIVE'}
            </span>
        </td>
        <td>${formatDateTime(employeeData.lastLogin)}</td>
        <td>
            <button class="btn btn-sm btn-primary" onclick="openEditModal(${employeeData.employeeID})">
                <i class="bi bi-pencil-square"></i>
            </button>
            <button class="btn btn-sm btn-danger" onclick="deleteEmployee(${employeeData.employeeID})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(newRow);
}

// Handle Edit Employee form submission with date validation.
document.getElementById('editEmployeeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const startDate = document.getElementById('edit_dateStart').value;
    const endDate = document.getElementById('edit_dateEnd').value;
    if (endDate && new Date(startDate) > new Date(endDate)) {
        alert("Start date must be earlier than end date.");
        return;
    }
    const formData = new FormData(this);
    fetch('./assets/controllers/manage_employees/update_employee.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the table row with new data
                const employeeID = document.getElementById('edit_employeeID').value;
                const newData = {
                    employeeID: employeeID,
                    nameEmployee: document.getElementById('edit_nameEmployee').value,
                    usernameEmployee: document.getElementById('edit_usernameEmployee').value,
                    dateStart: document.getElementById('edit_dateStart').value,
                    dateEnd: document.getElementById('edit_dateEnd').value,
                    locationEmployee: document.getElementById('edit_locationEmployee').value,
                    positionEmployee: document.getElementById('edit_positionEmployee').value,
                    employedEmployee: document.getElementById('edit_employedEmployee').checked ? 1 : 0
                };
                updateTableRow(employeeID, newData);
                editModalInstance.hide();
                alert('Employee updated successfully.');
            } else {
                alert('Error updating employee: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error updating employee:', error);
            alert('An error occurred while updating the employee.');
        });
});

// Function to delete an employee.
function deleteEmployee(employeeID) {
    if (confirm("Are you sure you want to delete this employee?")) {
        fetch("./assets/controllers/manage_employees/delete_employee.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "employeeID=" + encodeURIComponent(employeeID)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById("employee-" + employeeID);
                    if (row) {
                        row.remove();
                    }
                } else {
                    alert("Error: " + data.error);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while deleting the employee.");
            });
    }
}

// Functions for the "Add Account" modal.
function openAddModal() {
    // Reset the form before showing
    document.getElementById('addEmployeeForm').reset();
    // Show the modal
    addModalInstance.show();
}

// Handle Add Employee form submission with date validation.
document.getElementById('addEmployeeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const startDate = document.getElementById('add_dateStart').value;
    const endDate = document.getElementById('add_dateEnd').value;
    if (endDate && new Date(startDate) > new Date(endDate)) {
        alert("Start date must be earlier than end date.");
        return;
    }
    const formData = new FormData(this);
    fetch('./assets/controllers/manage_employees/add_employee.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add the new row to the table
                const newData = {
                    employeeID: data.employeeID,
                    nameEmployee: document.getElementById('add_nameEmployee').value,
                    usernameEmployee: document.getElementById('add_usernameEmployee').value,
                    dateStart: document.getElementById('add_dateStart').value,
                    dateEnd: document.getElementById('add_dateEnd').value,
                    locationEmployee: document.getElementById('add_locationEmployee').value,
                    positionEmployee: document.getElementById('add_positionEmployee').value,
                    employedEmployee: document.getElementById('add_employedEmployee').checked ? 1 : 0
                };
                addTableRow(newData);
                addModalInstance.hide();
                this.reset();
                alert('Employee added successfully.');
            } else {
                alert('Error adding employee: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error adding employee:', error);
            alert('An error occurred while adding the employee.');
        });
});

// Table sorting functionality
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('employeeTable');
    const headers = table.querySelectorAll('th.sortable');
    let currentSort = { column: null, direction: 'asc' };

    // Add click event to all sortable headers
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const column = header.dataset.sort;
            const direction = currentSort.column === column && currentSort.direction === 'asc' ? 'desc' : 'asc';
            
            // Update sort state
            currentSort = { column, direction };
            
            // Reset all headers
            headers.forEach(h => {
                h.classList.remove('asc', 'desc');
            });
            
            // Add sort class to current header
            header.classList.add(direction);
            
            // Sort the table
            sortTable(column, direction);
        });
    });

    function sortTable(column, direction) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // Sort the array of rows
        const sortedRows = rows.sort((a, b) => {
            const aValue = a.getAttribute('data-' + column);
            const bValue = b.getAttribute('data-' + column);
            
            // Determine if we're sorting numbers or strings
            if (column === 'id' || column === 'employed') {
                return direction === 'asc' 
                    ? parseInt(aValue) - parseInt(bValue)
                    : parseInt(bValue) - parseInt(aValue);
            } else if (column === 'start-date' || column === 'end-date') {
                // Special handling for dates
                const dateA = aValue ? new Date(aValue) : new Date(0);
                const dateB = bValue ? new Date(bValue) : new Date(0);
                
                return direction === 'asc' 
                    ? dateA - dateB
                    : dateB - dateA;
            } else {
                // String comparison
                return direction === 'asc' 
                    ? aValue.localeCompare(bValue)
                    : bValue.localeCompare(aValue);
            }
        });
        
        // Remove all rows from the table
        while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
        }
        
        // Add sorted rows back to the table
        sortedRows.forEach(row => {
            tbody.appendChild(row);
        });
    }
});

// Add event listener to document to ensure it runs after DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get current user position from a data attribute we'll add to the body tag
    const userPosition = document.body.getAttribute('data-position');
    const userLocation = document.body.getAttribute('data-location');
    
    // Location filter functionality for SUPERADMIN
    const locationFilter = document.getElementById('locationFilter');
    if (locationFilter) {
        locationFilter.addEventListener('change', function() {
            const selectedLocation = this.value;
            const tbody = document.querySelector('#employeeTable tbody');
            const rows = tbody.querySelectorAll('tr');
            
            rows.forEach(row => {
                const rowLocation = row.getAttribute('data-location');
                if (!selectedLocation || rowLocation === selectedLocation) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // If user is ADMIN, add validation and setup dropdowns
    if (userPosition === 'ADMIN') {
        // For edit form validation
        document.getElementById('editEmployeeForm').addEventListener('submit', function(e) {
            const positionValue = document.getElementById('edit_positionEmployee').value;
            if (positionValue === 'ADMIN' || positionValue === 'SUPERADMIN') {
                e.preventDefault();
                alert('You do not have permission to create or modify administrator accounts.');
                return false;
            }
        });
        
        // For add form validation
        document.getElementById('addEmployeeForm').addEventListener('submit', function(e) {
            const positionValue = document.getElementById('add_positionEmployee').value;
            if (positionValue === 'ADMIN' || positionValue === 'SUPERADMIN') {
                e.preventDefault();
                alert('You do not have permission to create administrator accounts.');
                return false;
            }
        });

        // Double-check: Remove any admin options that might have been generated
        const editPositionSelect = document.getElementById('edit_positionEmployee');
        const addPositionSelect = document.getElementById('add_positionEmployee');
        
        // Remove admin options from edit dropdown
        Array.from(editPositionSelect.options).forEach(option => {
            if (option.value === 'ADMIN' || option.value === 'SUPERADMIN') {
                editPositionSelect.removeChild(option);
            }
        });
        
        // Remove admin options from add dropdown
        Array.from(addPositionSelect.options).forEach(option => {
            if (option.value === 'ADMIN' || option.value === 'SUPERADMIN') {
                addPositionSelect.removeChild(option);
            }
        });
        
        // Make sure location dropdowns are disabled and set to the admin's location
        const editLocationSelect = document.getElementById('edit_locationEmployee');
        const addLocationSelect = document.getElementById('add_locationEmployee');
        
        // Ensure location fields are disabled
        editLocationSelect.disabled = true;
        addLocationSelect.disabled = true;
        
        // Set location values to the admin's location
        if (userLocation) {
            editLocationSelect.value = userLocation;
            addLocationSelect.value = userLocation;
        }
    }

    // Table sorting initialization 
    const table = document.getElementById('employeeTable');
    const headers = table.querySelectorAll('th.sortable');
    let currentSort = { column: null, direction: 'asc' };

    // Add click event to all sortable headers
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const column = header.dataset.sort;
            const direction = currentSort.column === column && currentSort.direction === 'asc' ? 'desc' : 'asc';
            
            // Update sort state
            currentSort = { column, direction };
            
            // Reset all headers
            headers.forEach(h => {
                h.classList.remove('asc', 'desc');
            });
            
            // Add sort class to current header
            header.classList.add(direction);
            
            // Sort the table
            sortTable(column, direction);
        });
    });

    function sortTable(column, direction) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // Sort the array of rows
        const sortedRows = rows.sort((a, b) => {
            const aValue = a.getAttribute('data-' + column);
            const bValue = b.getAttribute('data-' + column);
            
            // Determine if we're sorting numbers or strings
            if (column === 'id' || column === 'employed') {
                return direction === 'asc' 
                    ? parseInt(aValue) - parseInt(bValue)
                    : parseInt(bValue) - parseInt(aValue);
            } else if (column === 'start-date' || column === 'end-date') {
                // Special handling for dates
                const dateA = aValue ? new Date(aValue) : new Date(0);
                const dateB = bValue ? new Date(bValue) : new Date(0);
                
                return direction === 'asc' 
                    ? dateA - dateB
                    : dateB - dateA;
            } else {
                // String comparison
                return direction === 'asc' 
                    ? aValue.localeCompare(bValue)
                    : bValue.localeCompare(aValue);
            }
        });
        
        // Remove all rows from the table
        while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
        }
        
        // Add sorted rows back to the table
        sortedRows.forEach(row => {
            tbody.appendChild(row);
        });
    }
});