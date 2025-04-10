<?php
header('Content-Type: application/json');
include('includes/config.php');
include('includes/connect.php');
include('includes/function.php');
// include('includes/header.php');

secure();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$id = (int)$_GET['id'];

  // Fetch employee data
$query = 'SELECT * FROM mock_data WHERE id = ?';
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Employee not found']);
    exit;
}

echo json_encode($result->fetch_assoc());
?>