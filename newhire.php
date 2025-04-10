<?php

include('includes/config.php');
include('includes/connect.php');
include('includes/function.php');
include('includes/header.php');

admin_secure();


if (isset($_GET['approve_view'])) {
    $view = $_GET['approve_view'];
} else {
    $view = "pending";
}
$query = "SELECT * FROM employee_add WHERE updatedAt IS NOT NULL AND approvedBy IS NULL";

if ($view === "approved") {
    $query = "SELECT * FROM employee_add WHERE updatedAt IS NOT NULL AND approvedBy IS NOT NULL";
} elseif ($view === "all") {
    $query = "SELECT * FROM employee_add WHERE updatedAt IS NOT NULL";
}
else {
    $query = "SELECT * FROM employee_add WHERE updatedAt IS NOT NULL AND approvedBy IS NULL";
}

$entry_data = $connect->query($query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // First check for form-based rejection
    if (isset($_POST['reject_data'])) {
        handleRejection($connect);
        exit;
    }
    
    // Then handle JSON input for approval
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, 'Invalid JSON input');
    }
    
    if (isset($data['approve_data'])) {
        handleApproval($connect, $data);
    }
}

function handleRejection($connect) {
    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    sendJsonResponse(false, 'Invalid request');
    }

    $id = (int)($_POST['id'] ?? 0);
    $comment = $_POST['reject_comment'] ?? '';

    if ($id === 0) {
        sendJsonResponse(false, 'Invalid record ID');
    }

    if (empty($comment)) {
        sendJsonResponse(false, 'Rejection reason cannot be empty');
    }

    $query_reject = "UPDATE employee_add 
                    SET updatedAt = NULL, 
                        comment = ? 
                    WHERE id = ?";
    $stmt = $connect->prepare($query_reject);

    if (!$stmt) {
        error_log("Rejection prepare error: " . $connect->error);
        sendJsonResponse(false, 'Database error');
    }

    $stmt->bind_param('si', $comment, $id);

    if ($stmt->execute()) {
        error_log("Rejected record $id with comment: $comment");
        sendJsonResponse(true, 'Record rejected successfully');
    } else {
        error_log("Rejection execute error: " . $stmt->error);
        sendJsonResponse(false, 'Database error: ' . $stmt->error);
    }
}

function handleApproval($connect, $data) {
    try {
        $connect->begin_transaction();
        
        // Update employee_add table
        $id = (int)$data['id']; // Use $data, not $_POST
        $approvedEmail = $_SESSION['email'];
        
        $query_approvedEmail = "UPDATE employee_add SET approvedBy = ? WHERE id = ?";
        $stmt = $connect->prepare($query_approvedEmail);
        
        if (!$stmt) {
            throw new Exception("Prepare error: " . $connect->error);
        }
        
        $stmt->bind_param("si", $approvedEmail, $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute error: " . $stmt->error);
        }
        
        // Check if any row was actually updated
        if ($stmt->affected_rows === 0) {
            throw new Exception("No record found with ID: $id");
        }
        
        // Insert into mock_data table
        $required = ['id', 'first_name', 'last_name', 'email', 'gender', 'salary', 'position', 'hidden_size'];
        $missing = array_diff($required, array_keys($data));
        
        if (!empty($missing)) {
            throw new Exception('Missing required fields: ' . implode(', ', $missing));
        }

        // Sanitize inputs
        $firstName = filter_var($data['first_name'], FILTER_SANITIZE_STRING);
        $lastName = filter_var($data['last_name'], FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $gender = filter_var($data['gender'], FILTER_SANITIZE_STRING);
        $salary = filter_var($data['salary'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $position = filter_var($data['position'], FILTER_SANITIZE_STRING);
        $size = filter_var($data['hidden_size'], FILTER_SANITIZE_STRING);
        $createdBy = isset($data['createdBy']) ? filter_var($data['createdBy'], FILTER_SANITIZE_STRING) : '';
        $createdAt = isset($data['createdAt']) ? filter_var($data['createdAt'], FILTER_SANITIZE_STRING) : '';
        $approvedBy = $_SESSION['email'];

        $query_approve = "INSERT INTO mock_data 
                         (first_name, last_name, email, gender, salary, position, top_size, date_started, createdAt, createdBy, approvedAt, approvedBy) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, '0000-00-00', ?, ?, NOW(), ?)";
        
        $stmt = $connect->prepare($query_approve);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $connect->error);
        }
        
        $stmt->bind_param('ssssdsssss', 
            $firstName,
            $lastName,
            $email,
            $gender,
            $salary,
            $position,
            $size,
            $createdAt,
            $createdBy,
            $approvedBy
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }
                      
        $connect->commit();
        sendJsonResponse(true, 'Record approved and transferred successfully');
        header("Location: ".$_SERVER['PHP_SELF']);
    } catch (Exception $e) {
        $connect->rollback();
        sendJsonResponse(false, $e->getMessage());
    }
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function sendJsonResponse($success, $message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}



$query_size = 'SELECT * FROM `size`'; 
$sizes = $connect->query($query_size)->fetch_all(MYSQLI_ASSOC);
?>

<div class="flex-grow">
    <h1 class="text-center text-2xl mb-4 title">Employee Entry Data</h1>
    <div class="w-2/3 mx-auto">
        <div class="flex"> <!-- Changed from 'row' to 'flex' -->
            <div class="w-1/3"></div>
            <div class="w-1/3">
                <p class="text-center mt-5 text-gray-400 italic">Click the cell to edit.</p>
            </div>
            <div class="w-1/3 text-right ">
                <select name="approve_view" id="approve_view" class="w-1/2 mb-4 p-2 border rounded" 
                    onchange="window.location.href = '?approve_view=' + this.value">
                    <option value="pending" <?= ($view === 'pending') ? 'selected' : '' ?>>Pending on approval</option>    
                    <option value="approved" <?= ($view === 'approved') ? 'selected' : '' ?>>Approved Newhires</option>
                    <option value="all" <?= ($view === 'all') ? 'selected' : '' ?>>All Newhires</option>
                </select>
            </div>
        </div>

    

    <table id="employeeTable" class="w-full mt-0">
        <thead>
            <tr>
                <th>Temp ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Gender</th>               
                <th class="bg-amber-600">Salary</th>
                <th class="bg-amber-600">Position</th>
                <th class="bg-amber-600">Size</th>
                <th>SendAt</th>
                <th>Action</th>
            </tr>
        </thead>
   <tbody>
   <?php while ($record = mysqli_fetch_assoc($entry_data)): ?>
            <tr>
                <td><?php echo $record['id']; ?></td>
                <td ><?php echo $record['first_name']; ?></td>
                <td><?php echo $record['last_name']; ?></td>
                <td ><?php echo $record['email']; ?></td>
                <td ><?php echo $record['gender']; ?></td>        
                <td class="bg-amber-100 <?php echo $record['approvedBy'] ? '' : 'editable'; ?>" id="<?php echo $record['id']; ?>_salary"><?php echo $record['salary'] ?? 'null'; ?></td>
                <td class="bg-amber-100 <?php echo $record['approvedBy'] ? '' : 'editable'; ?>" id="<?php echo $record['id']; ?>_position"><?php echo $record['position'] ?? 'null'; ?></td>
                <td class="bg-amber-100">
                    <select name="size" class="<?php echo $record['approvedBy'] ? '' : 'editable text-black'; ?> border" <?php echo $record['approvedBy'] ? 'disabled' : ''; ?> id="<?php echo $record['id']; ?>_size">
                        <option value="" <?php echo empty($record['size']) ? 'selected' : ''; ?>>Please select</option>
                            <?php foreach ($sizes as $size): ?>
                                <option value="<?php echo $size['top_size']; ?>"
                                        <?php echo ($record['size'] ?? '') === $size['top_size'] ? 'selected' : ''; ?>>
                                        <?php echo $size['top_size']; ?>
                                </option>
                            <?php endforeach; ?>
                    </select>
                </td>
                <td class="text-sm"><?php echo $record['updatedAt']; ?></td>
                <td>
                    <div class="inline-flex gap-3">
                        <form method="post" onsubmit="return confirmApprove(event)">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($record['id']); ?>">
                            <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($record['first_name']); ?>">
                            <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($record['last_name']); ?>">
                            <input type="hidden" name="gender" value="<?php echo htmlspecialchars($record['gender']); ?>">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($record['email']); ?>">
                            <input type="hidden" name="salary" value="<?php echo htmlspecialchars($record['salary']); ?>">
                            <input type="hidden" name="position" value="<?php echo htmlspecialchars($record['position']); ?>">
                            <input type="hidden" name="hidden_size" value="<?php echo htmlspecialchars($record['size']); ?>">
                            <input type="hidden" name="createdAt" value="<?php echo htmlspecialchars($record['createdAt']); ?>">
                            <input type="hidden" name="createdBy" value="<?php echo htmlspecialchars($record['createdBy']); ?>">
                            <button type="submit" class="btn rounded <?php echo $record['approvedBy'] ? 'disabled-btn' : ''; ?>" name="approve_data" <?php echo $record['approvedBy'] ? 'disabled' : ''; ?>>Approve</button>
                        </form>
                        <form method="post" onsubmit="return confirmReject(event)">
                            <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                            <button type="submit" class="btn rounded <?php echo $record['approvedBy'] ? 'disabled-btn' : ''; ?>" name="reject_data" <?php echo $record['approvedBy'] ? 'disabled' : ''; ?>>Reject</button>
                        </form>              
                    </div>
                </td>
            <tr>
                <?php endwhile; ?>
   </tbody>
   </table>
   </div>
   </div>

   <?php

$connect->close();
include('includes/footer.php');
?>

   <script>
    $(document).ready(function () {
    console.log("Initializing editable fields...");
    
    // Store original values for all fields
    const originalData = {};
    
    $('.editable').each(function() {
        const $element = $(this);
        const elementId = $element.attr('id');
        const parts = elementId.split('_');
        const id = parts[0];
        const field = parts.slice(1).join('_');

         // Handle both text spans and select elements
         let currentValue;
        if ($element.is('select')) {
            currentValue = $element.val();
        } else {
            currentValue = $element.text().trim();
        }
        
        console.group(`Initializing field: ${field} for ID: ${id}`);
        console.log("Element ID:", elementId);
        console.log("Current value:", currentValue);

        // Initialize original data storage
        if (!originalData[id]) {
            originalData[id] = {};
            console.log(`Creating new record for ID ${id}`);
        }
        originalData[id][field] = currentValue;
        
        console.log(`Stored original value for ${field}:`, originalData[id][field]);
        console.groupEnd();

        // For select elements, we'll handle changes differently
        if ($element.is('select')) {
            $element.on('change', function() {
                const newValue = $(this).val();
                const oldValue = originalData[id][field];
                
                console.group(`Edit triggered for ${field} on ID ${id}`);
                console.log("Old value:", oldValue);
                console.log("New value:", newValue);

                if (newValue === oldValue) {
                    console.log("No changes detected - aborting");
                    console.groupEnd();
                    return; // No changes made
                }

                
                // Create new object with ONLY backend field names
                const currentData = {
                    salary: originalData[id]['salary'] || '',
                    position: originalData[id]['position'] || '',
                    size: originalData[id]['size'] || '',
                };
                
                // Update the changed field
                currentData[field] = newValue;
                
                console.log("Data to be sent to server:", currentData);
                
                // Show saving indicator
                const originalText = $element.html();
                $element.html('<option value="">Saving...</option>');
                
                console.log("Update database...");
                
                fetch(`http://localhost/database_flora/api/employee.php?id=${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(currentData)
                })
                .then(response => {

                     const row = this.closest('tr');
    if (row) {
        const form = row.querySelector('form');
        if (form) {
            const fieldInput = form.querySelector(`[name="${field}"]`);
            if (fieldInput) {
                fieldInput.value = newValue;
            }
        }
    }
                    const hiddenSizeInput = form.querySelector('[name="hidden_size"]');
                        if (hiddenSizeInput) {
                            hiddenSizeInput.value = newValue; 
                            console.log('Updated hidden size to:', newValue);
                        }
                    console.log("Received response, status:", response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json().catch(e => {
                        console.error("Failed to parse JSON:", e);
                        throw new Error("Invalid JSON response");
                    });
                })
                .then(result => {
                    console.log("API response:", result);
                    if (result.error) {
                        throw new Error(result.message || "Unknown server error");
                    }
                    
                    // Update original data storage
                    originalData[id][field] = newValue;
                    console.log("Update successful");
                    // Update the corresponding hidden form field instead of reloading
                     
                })
                .catch(error => {
                    console.error("Error during save:", error);
                    // alert("Save failed: " + error.message);
                    $element.val(oldValue);
                })
                .finally(() => {
                    // Restore the select options (they might have been modified during save)
                    $element.html(originalText);
                    $element.val(newValue);

                    console.groupEnd();
                });
            });
        } else {
            // Original text span handling remains the same
            $element.editable('click', function(data) {
                const newValue = data.value.trim();
                const oldValue = data.old_value.trim();
                
                console.group(`Edit triggered for ${field} on ID ${id}`);
                console.log("Old value:", oldValue);
                console.log("New value:", newValue);

                const processedValue = newValue === "" ? null : newValue;

                if (processedValue === oldValue) {
                    console.log("No changes detected - aborting");
                    console.groupEnd();
                    return; // No changes made
                }

               

                // Create new object with ONLY backend field names
                const currentData = {
                    salary: originalData[id]['salary'] || '',
                    position: originalData[id]['position'] || '',
                    size: originalData[id]['size'] || ''
                };

                        
                // Update the changed field
                currentData[field] = processedValue;
                
                console.log("Data to be sent to server:", currentData);
                
                // Show saving indicator
                data.target.text("Saving...");
                
                console.log("Initiating PUT request...");
                
                fetch(`http://localhost/database_flora/api/employee.php?id=${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(currentData)
                })
                .then(response => {
                    console.log("Received response, status:", response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json().catch(e => {
                        console.error("Failed to parse JSON:", e);
                        throw new Error("Invalid JSON response");
                    });
                })
                .then(result => {
                    console.log("API response:", result);
                    if (result.error) {
                        throw new Error(result.message || "Unknown server error");
                    }
                    
                    // Update original data storage
                    originalData[id][field] = newValue;
                    data.target.text(newValue);
                    window.location.reload();
                    console.log("Update successful");
                })
                .catch(error => {
                    console.error("Error during save:", error);
                    // alert("Save failed: " + error.message);
                    data.target.text(oldValue);
                })
                .finally(() => {
                    console.groupEnd();

                });
            });
        }
    });
});


function confirmReject(event) {
    event.preventDefault();
    const form = event.target.closest('form');
    
    // Show prompt for rejection reason
    const comment = window.prompt("Please enter rejection reason:");
    if (comment === null) {
        return false; // User cancelled
    }
    
    if (!comment.trim()) {
        alert("Rejection reason cannot be empty");
        return false;
    }

    // Create FormData and append all needed fields
    const formData = new FormData();
    formData.append('id', form.querySelector('[name="id"]').value);
    formData.append('reject_comment', comment);
    formData.append('reject_data', '1');

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // Only this header is needed
        }
    })
    .then(response => {
        // Always refresh the page regardless of response
        window.location.reload();
        return; // Exit the promise chain
    })
    .catch(error => {
        // Ignore any errors and still refresh
        window.location.reload();
    });
    
    return false;

}



    
function confirmApprove(event) {
    event.preventDefault();
    const form = event.target.closest('form');
    
    // 1. First show confirmation
    if (!confirm("Are you sure you want to approve this record?")) {
        return false;
    }
    
    // 2. Store approval intent in sessionStorage
    sessionStorage.setItem('pendingApproval', 'true');
    sessionStorage.setItem('approveFormData', JSON.stringify({
        id: form.querySelector('[name="id"]').value
    }));
    
    // 3. Refresh the page to get updated values
    window.location.reload();
}

// // On page load, check for pending approval
// window.addEventListener('DOMContentLoaded', () => {
//     if (sessionStorage.getItem('pendingApproval') === 'true') {
//         // Retrieve stored form data
//         const formData = JSON.parse(sessionStorage.getItem('approveFormData'));
        
//         // Find the form (wait a tick for dynamic content)
//         setTimeout(() => {
//             const form = document.querySelector(`form input[name="id"][value="${formData.id}"]`)?.closest('form');
//             if (form) {
//                 // Now submit with fresh data
//                 submitApproval(form);
//             }
            
//             // Clean up
//             sessionStorage.removeItem('pendingApproval');
//             sessionStorage.removeItem('approveFormData');
//         }, 50);
//     }
// });

// Actual submission logic
function submitApproval(form) {
    const formData = new FormData(form);
    const jsonData = {};
  
    const salary = formData.get('salary');
    const position = formData.get('position');
    // const size = formData.get('size');
    const size=formData.get('size') || form.querySelector('[name="hidden_size"]').value;

    console.log('Salary:', salary);
    console.log('Position:', position);
    console.log('getNameSize:', size);
    
    if (!salary || !position || !size || salary==="null" || position==="null" || size==="null") {
        alert('Error: Salary, Position, and Size fields cannot be empty');
        return false;
    }

 

    // Convert FormData to JSON object
    formData.forEach((value, key) => {
        jsonData[key] = value;
    });
    
    // Add approval flag
    jsonData['approve_data'] = 'true';

    console.log('JSON Data:', jsonData);

    fetch(form.action, {
    method: 'POST',
    body: JSON.stringify(jsonData),
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    })
    .then(response => {
        // Check if the request was successful (status 2xx)
        if (response.ok) {
            // Success - don't try to parse the response
            console.log('Request successful');
            // Optionally refresh the page or show success message
            alert('Record approved successfully!');
            window.location.reload(); // If you want to refresh the page
        } else {
            // Handle HTTP errors (4xx, 5xx)
            throw new Error(`Server error: ${response.status}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Only show error if it's not about JSON parsing
        if (!error.message.includes('JSON')) {
            alert(`Action failed: ${error.message}`);
        }
    });
    return false;
}




    </script>