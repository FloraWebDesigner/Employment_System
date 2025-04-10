<?php

include('includes/config.php');
include('includes/connect.php');
include('includes/function.php');
include('includes/header.php');

data_entry_secure();

// echo $_SESSION['email'];

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




$query_gender = 'SELECT * FROM `gender`'; 
$genders = $connect->query($query_gender)->fetch_all(MYSQLI_ASSOC);

?>

<div class="flex-grow">
    <h1 class="text-center text-2xl mb-4 title">Employee Data</h1>
    <div class="w-2/3 mx-auto">
    <form id="addEmployee" class="w-full text-center">
        <input type="text" name="firstName" id="addFName" placeholder="firstName" >
        <input type="text" name="lastName" id="addLName" placeholder="lastName" >
        <input type="email" name="email" id="addEmail" placeholder="email" >

        <select name="gender" id="addGender" placeholder="gender" class="border p-2 rounded border-gray-600 text-gray-400">
                        <option value="">Please select gender</option>
                            <?php foreach ($genders as $gender): ?>
                                <option value="<?php echo $gender['gender']; ?>">
                                        <?php echo $gender['gender']; ?>
                                </option>
                            <?php endforeach; ?>
        </select>

        <!-- <input type="text" name="gender" id="addGender" placeholder="gender" > -->
    <button type = "submit" class="btn rounded text-green-600">Add<i class="fa-solid fa-square-plus ms-3 text-green-600 hover:text-white"></i></button>
    </form>

<p class="text-center mt-5 text-gray-400 italic">Click the cell to edit.</p>
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

                    // <td><span class="editable" id="edit_${employee.id}_salary">none</span></td>
                    //     <td><span class="editable" id="edit_${employee.id}_position">none</span></td>
                    //     <td><span class="editable" id="edit_${employee.id}_size">none</span></td>
                    // <button id="dataEntryEdit_${employee.id}" onclick="editEmployee(${employee.id})" class="btn rounded">Edit</button>
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

// function editEmployee(id) {
//     console.log("call edit");
    
//     const formData = {
//         first_name: document.getElementById(`edit_${id}_first_name`).innerText,
//         last_name: document.getElementById(`edit_${id}_last_name`).innerText,
//         email: document.getElementById(`edit_${id}_email`).innerText,
//         gender: document.getElementById(`edit_${id}_gender`).innerText
//     };

//     console.log('Form data:', formData);

//     fetch(`http://localhost/database_flora/api/employee.php?id=${id}`, {
//         method: 'PUT',
//         headers: {
//             'Content-Type': 'application/json',
//         }, 
//         body: JSON.stringify(formData)     
//     }).then(response => response.json())
//     .then(data => {   
//         if (!data.error) {
//             window.location.href = 'add_salary.php';
//         } else {
//             alert(data.message);
//         }
//     })
//     .catch(error => console.error('Error:', error));
// }





    </script>
    
    <?php
    include('includes/footer.php');
    ?>
</body>
</html>