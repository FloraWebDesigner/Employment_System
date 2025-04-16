<?php

include('includes/config.php');
include('includes/connect.php');
include('includes/function.php');
include('includes/header.php');

data_entry_secure();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["import"])) {
    $filename = $_FILES["file"]["tmp_name"];
    
    // Check if the file is a CSV file
    if (pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION) != "csv") {
        echo "<script>alert('Please upload a CSV file.');</script>";
    } else {
        // Proceed with importing
        if (importCSV($filename, $connect)) {
            echo "<script>alert('CSV file imported successfully!');</script>";
        } else {
            echo "<script>alert('Import failed.');</script>";
        }
    }
}
// import csv file
if (isset($_POST["import"])) {
    $filename = $_FILES["file"]["tmp_name"];
    
    // Check if the file is a CSV file
    if (pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION) != "csv") {
        echo "Please upload a CSV file.";
        exit;
    }
    
    // Proceed with importing the CSV file
    importCSV($filename, $connect);
}

function importCSV($filename,$connect) {
    
    if (!file_exists($filename)) {
        console.log("File not found");
        return false;
    }

    $file = fopen($filename, "r");
    if (!$file) {
       console.log("Could not open file");
        return false;
    }

    // Skip header
    fgetcsv($file, 10000, ",");

    while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
        if (count($getData) < 1) {
            error_log("Skipping invalid row: " . implode(",", $getData));
            continue;
        }

        // Process data (same as your code)
        $id = $getData[0] ?? null;
        $first_name = $getData[1] ?? '';
        $last_name = $getData[2] ?? '';
        $email = $getData[3] ?? '';
        $gender = $getData[4] ?? null;
        $salary = $getData[7] ?? null;
        $position = $getData[8] ?? null;
        $size = $getData[9] ?? null;
        $comment = $getData[10] ?? null;

        // Check if record exists
        $checkSql = "SELECT id FROM employee_add WHERE id = ?";
        $checkStmt = $connect->prepare($checkSql); 
        $checkStmt->bind_param("i", $id);
        
        if (!$checkStmt->execute()) {
            error_log("Check query failed: " . $checkStmt->error);
            continue;
        }

        $result = $checkStmt->get_result();
        $exists = $result->num_rows > 0;
        $checkStmt->close();

        // Prepare the appropriate query
        if ($exists) {
            $sql = "UPDATE employee_add SET 
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    gender = ?,
                    updatedAt = NOW(),
                    salary = ?,
                    position = ?,
                    size = ?,
                    comment = ?
                    WHERE id = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("ssssdsssi", 
                $first_name, 
                $last_name, 
                $email, 
                $gender,
                $salary,
                $position,
                $size,
                $comment,
                $id
            );
        } else {
            $sql = "INSERT INTO employee_add (
                    first_name, 
                    last_name, 
                    email, 
                    gender, 
                    createdAt, 
                    salary, 
                    position, 
                    size, 
                    comment,
                    createdBy
                ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("ssssdssss", 
                $first_name, 
                $last_name, 
                $email, 
                $gender,
                $salary,
                $position,
                $size,
                $comment,
                $_SESSION['role']
            );
        }

        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
        }
        $stmt->close();
    }

    fclose($file);

    return true;
}

// export data to csv, text, json
if (isset($_POST['export_type'])) {
    ob_end_clean();
    
    $query_download = "SELECT * FROM employee_add ORDER BY id ASC";
    $result_download = mysqli_query($connect, $query_download);
    
    if ($result_download === false) {
        die("Query failed: " . mysqli_error($connect));
    }
    
    $data_download = [];
    while ($row_download = mysqli_fetch_assoc($result_download)) {
        $data_download[] = $row_download;
    }
    
    switch ($_POST['export_type']) {
        case 'csv':
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=employees.csv');
            $output_download = fopen('php://output', 'w');
            
            // Write headers
            if (!empty($data_download)) {
                fputcsv($output_download, array_keys($data_download[0]));
            }
            
            // Write data
            foreach ($data_download as $row_download) {
                fputcsv($output_download, $row_download);
            }
            fclose($output_download);
            break;
            
        case 'text':
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename=employees.txt');
            
            // Write headers
            if (!empty($data_download)) {
                echo implode("\t", array_keys($data_download[0])) . "\n";
            }
            
            // Write data
            foreach ($data_download as $row_download) {
                echo implode("\t", $row_download) . "\n";
            }
            break;
            
        case 'json':
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename=employees.json');
            echo json_encode($data_download, JSON_PRETTY_PRINT);
            break;
    }
    
    exit;
}



// after creating employee, send it for approval

if(isset($_POST['sendEmployee'])) {
    $id = $_POST['id'];
    
    // Update only the updatedAt timestamp
    $query = "UPDATE employee_add SET updatedAt = NOW(), comment = NULL WHERE id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Redirect back to prevent form resubmission
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}



// get drop-down list for gender
$query_gender = 'SELECT * FROM `gender`'; 
$genders = $connect->query($query_gender)->fetch_all(MYSQLI_ASSOC);

?>

<div class="flex-grow">
<div class="w-2/3 mx-auto">
    <h1 class="text-center text-2xl mb-4 title">Employee Data</h1>
    <div class="w-full flex justify-end">
        <span id="fileName" class="text-sm text-gray-500 ml-2 truncate max-w-xs"></span>
</div>
        <div class="flex justify-between align-center">

                <form id="addEmployee" class="w-3/4 text-center flex align-end justify-center gap-1">
                    <input type="text" name="firstName" id="addFName" placeholder="first name" class="w-1/6">
                    <input type="text" name="lastName" id="addLName" placeholder="last name" class="w-1/6">
                    <input type="email" name="email" id="addEmail" placeholder="email" class="w-2/6">

                    <select name="gender" id="addGender" placeholder="gender" class="border p-2 rounded border-gray-600 text-gray-400 w-1/6 self-center">
                                    <option value="">Select gender</option>
                                        <?php foreach ($genders as $gender): ?>
                                            <option value="<?php echo $gender['gender']; ?>">
                                                    <?php echo $gender['gender']; ?>
                                            </option>
                                        <?php endforeach; ?>
                    </select>

                    <!-- <input type="text" name="gender" id="addGender" placeholder="gender" > -->
                <button type = "submit" class="btn rounded text-green-600 w-1/6 self-center">Add<i class="fa-solid fa-square-plus ms-3 text-green-600 hover:text-white"></i></button>
                </form>
                <form action="add_salary.php" method="post" enctype="multipart/form-data" class="w-1/4 flex justify-end items-center gap-2">
                    <div class="relative">
                        <input type="file" name="file" id="csvUpload" accept=".csv" class="opacity-0 absolute w-full h-full" onchange="showFileName()">
                        <button type="button" class="upload btn rounded text-green-600 self-center p-2">
                            Choose CSV File
                        </button>
                    </div>
                    <button type="submit" name="import" class="btn rounded text-green-600 p-2">Upload
                        <i class="fa-solid fa-upload ms-1 text-green-600 hover:text-white"></i>
                    </button>
                </form>
            </div>

    <div class="flex align-center justify-between mt-5">
        <div class="w-1/3"></div>
        <div class="w-1/3"><p class="text-center text-gray-400 italic">Click the cell to edit.</p></div>
        <div class="w-1/3 text-end flex flex-row gap-5 justify-end text-gray-400">

            <form method="post" class="inline-form">
                <input type="hidden" name="export_type" value="csv">
                <button type="submit" class="export-btn hover:text-green-600">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
            </form>
            
            <form method="post" class="inline-form">
                <input type="hidden" name="export_type" value="text">
                <button type="submit" class="export-btn hover:text-green-600">
                    <i class="fas fa-file-alt"></i> Text
                </button>
            </form>
            
            <form method="post" class="inline-form">
                <input type="hidden" name="export_type" value="json">
                <button type="submit" class="export-btn hover:text-green-600">
                    <i class="fas fa-file-code"></i> JSON
                </button>
            </form>
        </div>
    </div>
    <table id="employeeTable" class="w-full">
        <thead>
            <tr>
                <th>Temp ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Gender</th>
                <th>SendAt</th>
                <!-- <th>Salary</th>
                <th>Position</th>
                <th>Size</th> -->
                <th>Message</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <!-- Data will be inserted here by JavaScript -->
        </tbody>
    </table>
    </div>
</div>

    <script>
        window.genders = <?= json_encode($genders) ?>;

    // single click

        // Fetch data from your API
        fetch('http://localhost/database_flora/api/employee.php')
            .then(response => response.json())
            .then(data => {
                const tableBody = document.querySelector('#employeeTable tbody');                
                // Clear any existing rows
                tableBody.innerHTML = '';
                
                // Add new rows for each employee
                data.forEach(employee => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                    
                        <td><span id="edit_${employee.id}">${employee.id}</span></td>
                        <td><span class="editable" id="edit_${employee.id}_firstName" >${employee.first_name?.trim() || 'null'}</span></td>
                        <td><span class="editable" id="edit_${employee.id}_lastName">${employee.last_name?.trim() || 'null'}</span></td>
                        <td><span class="editable" id="edit_${employee.id}_email">${employee.email?.trim() || 'null'}</span></td>
                        <td class="text-center">
                    <select name="gender" class="editable" id="edit_${employee.id}_gender">
                        ${window.genders.map(gender => 
                            `<option value="${gender.gender}" ${employee.gender === gender.gender ? 'selected' : ''}>
                                ${gender.gender}
                            </option>`
                        ).join('')}
                    </select>
                <td class="text-center">${employee.updatedAt || ""}</td>
                <td>${employee.comment || ""}</td>
                <td class="text-center">
                    <div class="inline-flex gap-3">
                        <button id="dataEntryDel_${employee.id}" onClick="delEmployee(${employee.id})" class="${employee.updatedAt ? 'disabled-btn' : ''}"><i class="fa-solid fa-trash text-red-500 hover:text-red-700"></i></button>
                        <form method="post">
                            <input type="hidden" name="id" value="${employee.id}">
                            <button type="submit" name="sendEmployee" class=" ${employee.updatedAt ? 'disabled-btn' : ''}" ><i class="fa-solid fa-arrow-up-right-from-square text-green-600 hover:text-green-800"></i></button>  
                         </form>  
                    </div>              
                </td>
                        
                    `;
                    tableBody.appendChild(row);
                });

                $(document).ready(function () {
    console.log("Initializing editable fields...");
    
    // Store original values for all fields
    const originalData = {};
    
    $('.editable').each(function() {
        const $element = $(this);
        const elementId = $element.attr('id');
        const parts = elementId.split('_');
        const id = parts[1];
        const field = parts.slice(2).join('_');
        
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
                
                // Field name mapping
                const apiFieldMap = {
                    'firstName': 'first_name',
                    'lastName': 'last_name',
                    'email': 'email',
                    'gender': 'gender'
                };
                const apiFieldName = apiFieldMap[field] || field;

                // Create new object with ONLY backend field names
                const currentData = {
                    first_name: originalData[id]['firstName'] || '',
                    last_name: originalData[id]['lastName'] || '',
                    email: originalData[id]['email'] || '',
                    gender: originalData[id]['gender'] || ''
                };
                
                // Update the changed field
                currentData[apiFieldName] = newValue;
                
                console.log("Data to be sent to server:", currentData);
                
                // Show saving indicator
                const originalText = $element.html();
                $element.html('<option value="">Saving...</option>');
                
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
                    console.log("Update successful");
                })
                .catch(error => {
                    console.error("Error during save:", error);
                    alert("Save failed: " + error.message);
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

                if (newValue === oldValue) {
                    console.log("No changes detected - aborting");
                    console.groupEnd();
                    return; // No changes made
                }
                
                // Field name mapping
                const apiFieldMap = {
                    'firstName': 'first_name',
                    'lastName': 'last_name',
                    'email': 'email',
                    'gender': 'gender'
                };
                const apiFieldName = apiFieldMap[field] || field;

                // Create new object with ONLY backend field names
                const currentData = {
                    first_name: originalData[id]['firstName'] || '',
                    last_name: originalData[id]['lastName'] || '',
                    email: originalData[id]['email'] || '',
                    gender: originalData[id]['gender'] || ''
                };
                
                // Update the changed field
                currentData[apiFieldName] = newValue;
                
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
                    console.log("Update successful");
                })
                .catch(error => {
                    console.error("Error during save:", error);
                    alert("Save failed: " + error.message);
                    data.target.text(oldValue);
                })
                .finally(() => {
                    console.groupEnd();
                });
            });
            }
            });
        });


        })
            .catch(error => {
                console.error('Error fetching data:', error);
                document.querySelector('#employeeTable tbody').innerHTML = `
                    <tr><td colspan="5">Error loading data. Make sure your API is running.</td></tr>
                `;
            });

    // Form submission handler - add employee
    document.getElementById('addEmployee').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = {
        first_name: document.getElementById('addFName').value,
        last_name: document.getElementById('addLName').value,
        email: document.getElementById('addEmail').value,
        gender: document.getElementById('addGender').value,
    };

    fetch('http://localhost/database_flora/api/employee.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (!data.error) {
            console.log(data);
            this.reset();
            // Refresh employee list
            loadEmployees();
            window.location.href = 'add_salary.php';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
        });
    });

// Function to load employees (reuse your existing fetch code)
function loadEmployees() {
    fetch('http://localhost/database_flora/api/employee.php')
        .then(response => response.json())
        .then(data => {            
        });
}

function delEmployee(id) {
    const confirm = window.confirm("Are you sure you want to delete the item?");
    console.log("run delete")
    if(confirm){
    fetch(`http://localhost/database_flora/api/employee.php?id=${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },      
    }).then(response => response.json())
    .then(data => {   
        if (!data.error) {
            window.location.href = 'add_salary.php';
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));}
}



function showFileName() {
    const fileInput = document.getElementById('csvUpload');
    const fileNameDisplay = document.getElementById('fileName');
    
    if (fileInput.files.length > 0) {
        fileNameDisplay.textContent = "Selected: " + fileInput.files[0].name;
        fileNameDisplay.classList.remove('hidden');
    } else {
        fileNameDisplay.textContent = '';
        fileNameDisplay.classList.add('hidden');
    }
}



    </script>
    
    <?php
    include('includes/footer.php');
    ?>
</body>
</html>