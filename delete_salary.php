<?php
header('Content-Type: application/json');
include('includes/config.php');
include('includes/connect.php');
include('includes/function.php');

secure(); // Your auth check

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("Invalid user ID");
    }

    $id = (int)$_GET['id'];
    
    $stmt = $connect->prepare("DELETE FROM mock_data WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database error: " . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}