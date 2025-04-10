<?php

include('includes/config.php');
include('includes/connect.php');
include('includes/function.php');
include('includes/header.php');

admin_secure();

if(isset($_POST['editAdmin'])){

    $id = $_POST['id'];

    if (isset($_POST['gender'])) {
        $selected_genders = $_POST['gender']; 
        $gender_list = implode(", ", $selected_genders);
    }

    if (isset($_POST['size'])) {
        $selected_sizes = $_POST['size']; 
        $size_list = implode(", ", $selected_sizes);
    }
 
    // Start the update query
    $query = "UPDATE `admin` SET 
    `access_gender`='" . mysqli_real_escape_string($connect, $gender_list) . "',
    `access_size` ='" . mysqli_real_escape_string($connect, $size_list) . "'";
 
 
    $query .= " WHERE `id` = '" . mysqli_real_escape_string($connect, $id) . "'";
 
    // Execute the query
    mysqli_query($connect, $query);
 
    // Redirect after successful edit
    header('location: permission.php');
    exit();    
 }
 
 ?>

<div>

<?php
// Fetch the existing record with error handling
$query = "SELECT * FROM `admin` WHERE id = ?";
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $_GET['edit_id']);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record) die("Record not found");

// Debug output - check what's actually stored
echo "<!-- Debug: access_gender = " . htmlspecialchars($record['access_gender']) . " -->";
echo "<!-- Debug: access_size = " . htmlspecialchars($record['access_size']) . " -->";

// Convert stored values to arrays (with trim to remove any whitespace)
$current_genders = array_map('trim', explode(',', $record['access_gender'] ?? ''));
$current_sizes = array_map('trim', explode(',', $record['access_size'] ?? ''));

// Fetch all possible options
$genders = $connect->query("SELECT * FROM `gender`")->fetch_all(MYSQLI_ASSOC);
$sizes = $connect->query("SELECT * FROM `size`")->fetch_all(MYSQLI_ASSOC);
?>

<h1 class="text-2xl text-center">Edit Admin Permission - <?= htmlspecialchars($record['email']) ?></h1>
<form class="w-1/4 mx-auto mt-10" method="post">
    <input type="hidden" name="id" value="<?= $record['id'] ?>">

    <div class="mb-4">
        <label class="w-full font-medium">Gender</label>
        <div class="flex flex-row flex-wrap">
            <?php foreach ($genders as $gender): ?>
                <div class="w-1/2">
                    <input type="checkbox" name="gender[]" 
                           value="<?= htmlspecialchars($gender['gender']) ?>"
                           <?= in_array($gender['gender'], $current_genders) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($gender['gender']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mb-4">
        <label class="w-full font-medium">Size</label>
        <div class="flex flex-row justify-around">
            <?php foreach ($sizes as $size): ?>
                <div>
                    <input type="checkbox" name="size[]" 
                           value="<?= htmlspecialchars($size['top_size']) ?>"
                           <?= in_array($size['top_size'], $current_sizes) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($size['top_size']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <button type="submit" class="bg-slate-200 px-8 py-2 text-black border border-gray-700 rounded hover:bg-slate-300 w-full" name="editAdmin">
        Update Permission
    </button>
</form>
</div>
 