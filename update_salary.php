<?php
header('Content-Type: application/json');
include('includes/config.php');
include('includes/connect.php');
include('includes/function.php');

secure();
try {
    // Validate input
    $required = ['edit_id', 'firstName', 'lastName', 'email', 'salary'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Process checkboxes (gender and size)
    $genders = isset($_POST['gender']) ? implode(',', $_POST['gender']) : '';
    $sizes = isset($_POST['size']) ? implode(',', $_POST['size']) : '';
 // Debug output (view in Network tab)
 error_log("Genders: " . print_r($genders, true));
 error_log("Sizes: " . print_r($sizes, true));

    // Prepare update query
    $stmt = $connect->prepare("
        UPDATE mock_data SET
            first_name = ?,
            last_name = ?,
            email = ?,
            position = ?,
            salary = ?,
            gender = ?,
            top_size = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssdssi",
        $_POST['firstName'],
        $_POST['lastName'],
        $_POST['email'],
        $_POST['position'],
        $_POST['salary'],
        $genders,
        $sizes,
        $_POST['edit_id']
    );

    if (!$stmt->execute()) {
        throw new Exception("Database update failed");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Employee updated successfully',
        'reload' => true
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}