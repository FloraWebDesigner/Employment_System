<?php

include('includes/config.php');
include('includes/connect.php');
include('includes/function.php');
include('includes/header.php');

secure();


// echo $_SESSION['access_gender'];
// echo $_SESSION['access_size'];

// https://codeshack.io/how-to-sort-table-columns-php-mysql/
$columns = array('id','first_name','last_name','email','gender','salary','position','top_size','date_started');
$column = isset($_GET['column']) && in_array($_GET['column'], $columns) ? $_GET['column'] : $columns[0];
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) == 'desc' ? 'DESC' : 'ASC';

$access_gender = isset($_SESSION['access_gender']) ? $_SESSION['access_gender'] : '*';
$access_size = isset($_SESSION['access_size']) ? $_SESSION['access_size'] : '*';


// Prepare base query
$query = 'SELECT DISTINCT SQL_CALC_FOUND_ROWS * FROM `mock_data` WHERE 1=1';
$params = array();
$types = '';


// Pagination settings
$items_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $items_per_page;


// Handle gender filter
if ($access_gender !== '*') {
    $gender_values = array_map('trim', explode(',', $access_gender));
    $placeholders = implode(',', array_fill(0, count($gender_values), '?'));
    $query .= " AND gender IN ($placeholders)";
    $types .= str_repeat('s', count($gender_values));
    $params = array_merge($params, $gender_values);
}

// Handle size filter
if ($access_size !== '*') {
    $size_values = array_map('trim', explode(',', $access_size));
    $placeholders = implode(',', array_fill(0, count($size_values), '?'));
    $query .= " AND top_size IN ($placeholders)";
    $types .= str_repeat('s', count($size_values));
    $params = array_merge($params, $size_values);
}

// Add sorting
$query .= " ORDER BY $column $sort_order";

// Add LIMIT for paginated results
$queryWithLimit = $query . " LIMIT ?, ?";
$types .= 'ii'; // Add two integers for LIMIT
$params[] = $start;
$params[] = $items_per_page;

// Debug output
error_log("Final Query: " . $queryWithLimit);
error_log("Parameters: " . print_r($params, true));

// Execute paginated query
$stmt = $connect->prepare($queryWithLimit);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get total count (more efficient than running the query twice)
$total_result = $connect->query("SELECT FOUND_ROWS()");
$num_rows = $total_result->fetch_row()[0];
$num_pages = ceil($num_rows / $items_per_page);


if ($result->num_rows > 0) {
	// Some variables we need for the table.
	$up_or_down = str_replace(array('ASC','DESC'), array('up','down'), $sort_order); 
	$asc_or_desc = $sort_order == 'ASC' ? 'desc' : 'asc';
	$add_class = ' class="highlight"';
	?>

<div class="pagination text-center mt-5">
    <!-- Previous Button -->
    <a href="<?= $page > 1 ? '?page='.($page-1).'&column='.$column.'&order='.$sort_order : '#' ?>" 
       class="<?= $page <= 1 ? 'disabled' : '' ?>">
        Previous
    </a>
    
    <!-- Page Numbers -->
    <?php for ($i = 1; $i <= $num_pages; $i++): ?>
        <a href="?page=<?= $i ?>&column=<?= $column ?>&order=<?= $sort_order ?>" 
           class="<?= $i == $page ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
    
    <!-- Next Button -->
    <a href="<?= $page < $num_pages ? '?page='.($page+1).'&column='.$column.'&order='.$sort_order : '#' ?>" 
       class="<?= $page >= $num_pages ? 'disabled' : '' ?>">
        Next
    </a>
</div>
  <table>
    <thead>
        <tr>
            <th class="w-[5%] min-w-[50px]"><a href="index.php?column=id&order=<?php echo $asc_or_desc; ?>">Id<i class="fas fa-sort<?php echo $column == 'id' ? '-' . $up_or_down : ''; ?>"></i></a></th>
            <th class="w-[10%] min-w-[100px]"><a href="index.php?column=first_name&order=<?php echo $asc_or_desc; ?>">First Name<i class="fas fa-sort<?php echo $column == 'first_name' ? '-' . $up_or_down : ''; ?>"></i></a></th>
            <th class="w-[10%] min-w-[100px]"><a href="index.php?column=last_name&order=<?php echo $asc_or_desc; ?>">Last Name<i class="fas fa-sort<?php echo $column == 'last_name' ? '-' . $up_or_down : ''; ?>"></i></a></th>
            <th class="w-[20%] min-w-[150px]"><a href="index.php?column=email&order=<?php echo $asc_or_desc; ?>">Email<i class="fas fa-sort<?php echo $column == 'email' ? '-' . $up_or_down : ''; ?>"></i></a></th>
            <th class="w-[8%] min-w-[80px]"><a href="index.php?column=gender&order=<?php echo $asc_or_desc; ?>">Gender<i class="fas fa-sort<?php echo $column == 'gender' ? '-' . $up_or_down : ''; ?>"></i></a></th>
            <th class="w-[8%] min-w-[80px]"><a href="index.php?column=salary&order=<?php echo $asc_or_desc; ?>">Salary<i class="fas fa-sort<?php echo $column == 'salary' ? '-' . $up_or_down : ''; ?>"></i></a></th>
            <th class="w-[15%] min-w-[120px]"><a href="index.php?column=position&order=<?php echo $asc_or_desc; ?>">Position<i class="fas fa-sort<?php echo $column == 'position' ? '-' . $up_or_down : ''; ?>"></i></a></th>
            <th class="w-[6%] min-w-[60px]"><a href="index.php?column=top_size&order=<?php echo $asc_or_desc; ?>">Size<i class="fas fa-sort<?php echo $column == 'top_size' ? '-' . $up_or_down : ''; ?>"></i></a></th>
            <th class="w-[10%] min-w-[100px]"><a href="index.php?column=date_started&order=<?php echo $asc_or_desc; ?>">Start Date<i class="fas fa-sort<?php echo $column == 'date_started' ? '-' . $up_or_down : ''; ?>"></i></a></th>
            <th class="w-[6%] min-w-[60px]">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
            <td <?php echo $column == 'id' ? $add_class : ''; ?>><?php echo $row['id']; ?></td>
            <td <?php echo $column == 'first_name' ? $add_class : ''; ?>><?php echo $row['first_name']; ?></td>
            <td <?php echo $column == 'last_name' ? $add_class : ''; ?>><?php echo $row['last_name']; ?></td>
            <td <?php echo $column == 'email' ? $add_class : ''; ?>><?php echo $row['email']; ?></td>
            <td <?php echo $column == 'gender' ? $add_class : ''; ?>><?php echo $row['gender']; ?></td>
            <td <?php echo $column == 'salary' ? $add_class : ''; ?>><?php echo $row['salary']; ?></td>
            <td <?php echo $column == 'position' ? $add_class : ''; ?>><?php echo $row['position']; ?></td>
            <td <?php echo $column == 'top_size' ? $add_class : ''; ?>><?php echo $row['top_size']; ?></td>
            <td <?php echo $column == 'date_started' ? $add_class : ''; ?>><?php echo $row['date_started']; ?></td>
            <td>
            <div class="flex flex-row justify-center gap-3">
                <!-- Modal toggle -->
                <div class="flex flex-row justify-center gap-3">
                    <!-- Edit Button -->
                    <a href="#" class="edit-btn" onclick="loadEditData(<?php echo $row['id']; ?>); return false;">
                        <i class="fa-solid fa-pen-to-square text-green-500 hover:text-green-700"></i>
                    </a>
                    <!-- Delete Button -->
                    <a href="javascript:void(0);" onclick="confirmDeleteUser(<?php echo $row['id']; ?>, '<?php echo $row['email']; ?>')" >
                        <i class="fa-solid fa-trash text-red-400 hover:text-red-600"></i>
                    </a>
            </div>
            </td> <!-- Closing table cell -->
            </tr> <!-- Closing table row -->
            <?php endwhile; ?> <!-- End of while loop for table rows -->
            </tbody>
            </table>
            <?php 
            $result->free(); // Free the result set
            }
            ?>

            <?php
    $query_gender = 'SELECT * FROM `gender`'; 
    $genders = $connect->query($query_gender);       
    $query_size = 'SELECT * FROM `size`'; 
    $sizes = $connect->query($query_size);        

            ?>

<!-- Modal Structure (keep this once) -->
<div id="ModalOnSalaryEdit" tabindex="-1" aria-hidden="true" class="hidden fixed inset-0 z-50 flex justify-center items-center bg-black bg-opacity-50">
  <div class="relative p-4 w-full max-w-md">
    <div class="bg-white rounded-lg shadow p-6">
      <h2 class="text-xl font-bold mb-4">Edit Employee</h2>
      <form id="editForm">
        <input type="hidden" id="edit_id" name="edit_id">
        
        <label class="block mt-2">First Name:</label>
        <input type="text" id="firstName" name="firstName" class="border p-2 w-full">
        <label class="block mt-2">Last Name:</label>
        <input type="text" id="lastName" name="lastName" class="border p-2 w-full">

        <label class="block mt-2">Email</label>
        <input type="text" id="email" name="email" class="border p-2 w-full">

        <label for="gender" class="w-full font-medium">Gender</label>
        <div class="flex flex-row flex-wrap">
            <?php foreach ($genders as $gender): ?>
                <div class="w-1/2">
                    <input type="checkbox" name="gender[]" value="<?= htmlspecialchars($gender['gender']) ?>">
                    <?= htmlspecialchars($gender['gender']) ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <label class="block mt-2">Position</label>
        <input type="text" id="position" name="position" class="border p-2 w-full">
    
        <label for="size" class="w-full font-medium">Size</label>
        <div class="flex flex-row justify-around">
            <?php foreach ($sizes as $size): ?>
                <div>
                    <input type="checkbox" name="size[]" value="<?= htmlspecialchars($size['top_size']) ?>">
                    <?= htmlspecialchars($size['top_size']) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <label class="block mt-2">Salary</label>
        <input type="text" id="salary" name="salary" class="border p-2 w-full" >
        
        <div class="flex justify-end space-x-4 mt-4">
          <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-700" onclick="closeModal()">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>



<?php
$connect->close();
include('includes/footer.php');
?>

<script>
    // Define the function globally (not inside DOMContentLoaded)
function loadEditData(id) {

console.log('Attempting to open modal for ID:', id); // Debug log

try {
// Show modal immediately (loading state)
const modal = document.getElementById('ModalOnSalaryEdit');
if (!modal) {
    throw new Error('Modal element not found');
}

console.log('Modal element found:', modal); // Debug log
modal.classList.remove('hidden');
console.log('Hidden class removed'); // Debug log

fetch(`get_salary.php?id=${id}`)
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json();
    })
    .then(data => {
        
        document.getElementById('edit_id').value = data.id;
        document.getElementById('firstName').value = data.first_name || '';
        document.getElementById('lastName').value = data.last_name || '';
        document.getElementById('email').value = data.email || '';
        document.getElementById('position').value = data.position || '';
        document.getElementById('salary').value = data.salary || '';
        
        // Handle checkboxes
        const setCheckboxes = (name, values) => {
                    const valueArray = values ? values.split(',') : [];
                    document.querySelectorAll(`input[name="${name}"]`).forEach(checkbox => {
                        checkbox.checked = valueArray.includes(checkbox.value);
                    });
                };
                
                setCheckboxes('gender[]', data.gender);
                setCheckboxes('size[]', data.top_size);
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Error: ' + error.message);
                document.getElementById('ModalOnSalaryEdit').classList.add('hidden');
            });
    } catch (error) {
        console.error('Modal error:', error);
        alert('Error: ' + error.message);
    }
    
    return false; // Prevent default anchor behavior
}

// Close modal function
function closeModal() {
    document.getElementById('ModalOnSalaryEdit').classList.add('hidden');
}



function confirmDeleteUser(id, email) {
    console.log("delete salary");
    if (confirm(`Are you sure you want to delete ${email}? This action cannot be undone.`)) {
        // Send delete request to server
        fetch(`delete_salary.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => {
            if (!response.ok) throw new Error('Delete failed');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('User deleted successfully!');
                location.reload(); // Refresh page
            } else {
                throw new Error(data.message || 'Delete failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        });
    }
}

            </script>
            <script src="/database_flora/includes/script.js"></script>