<?php
/**
 * @OA\Info(
 *     title="Employee API",
 *     version="1.0.0",
 *     description="API for managing employees"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost/database_flora/api",
 *     description="Local development server"
 * )
 */

/**
 * @OA\Get(
 *     path="/employee.php",
 *     summary="Get all employees or a specific employee by ID",
 *     @OA\Parameter(
 *         name="id",
 *         in="query",
 *         description="The ID of the employee to retrieve",
 *         required=false,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Employee")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad Request"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/employee.php",
 *     summary="Add a new employee",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"first_name", "last_name", "email", "gender"},
 *             @OA\Property(property="first_name", type="string"),
 *             @OA\Property(property="last_name", type="string"),
 *             @OA\Property(property="email", type="string"),
 *             @OA\Property(property="gender", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Employee added successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Missing required fields"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Failed to add employee"
 *     )
 * )
 */

/**
 * @OA\Put(
 *     path="/employee.php",
 *     summary="Update an existing employee",
 *     @OA\Parameter(
 *         name="id",
 *         in="query",
 *         description="The ID of the employee to update",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="first_name", type="string"),
 *             @OA\Property(property="last_name", type="string"),
 *             @OA\Property(property="email", type="string"),
 *             @OA\Property(property="gender", type="string"),
 *             @OA\Property(property="salary", type="number"),
 *             @OA\Property(property="position", type="string"),
 *             @OA\Property(property="size", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Employee updated successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid employee ID"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Failed to update employee"
 *     )
 * )
 */

/**
 * @OA\Delete(
 *     path="/employee.php",
 *     summary="Delete an employee",
 *     @OA\Parameter(
 *         name="id",
 *         in="query",
 *         description="The ID of the employee to delete",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Employee deleted successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid employee ID"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Failed to delete employee"
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="Employee",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="first_name", type="string"),
 *     @OA\Property(property="last_name", type="string"),
 *     @OA\Property(property="email", type="string"),
 *     @OA\Property(property="gender", type="string"),
 *     @OA\Property(property="salary", type="number"),
 *     @OA\Property(property="position", type="string"),
 *     @OA\Property(property="size", type="string")
 * )
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

include('../includes/connect.php');
include('../includes/function.php');
include('../includes/config.php');
include('db.php');

secure();

$api = $_SERVER['REQUEST_METHOD'];
$id = intval($_GET['id'] ?? 0);

// Handle preflight requests
if ($api === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function message($message, $error = false) {
    return json_encode(['error' => $error, 'message' => $message]);
}

function test_input($data) {
    if (!isset($data)) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}


// Handle GET
if ($api == 'GET') {
    $data = fetchEmployees($connect, $id);
    echo json_encode($data);
}

// Handle POST requests
if ($api == 'POST') {
    // Get the raw POST data
    $postData = file_get_contents('php://input');
    
    // Try to decode JSON first
    $input = json_decode($postData, true);
    
    // If not JSON, try regular POST data
    if (json_last_error() !== JSON_ERROR_NONE) {
        $input = $_POST;
    }

    // Validate required fields
    $required = ['first_name', 'last_name', 'email', 'gender'];
    $missing = array_diff($required, array_keys($input));
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        exit;
    }

    $createdBy = $_SESSION['email'] ?? 'admin@default.com';
    // Process data with sanitization
    $first_name = test_input($input['first_name'] ?? '');
    $last_name = test_input($input['last_name'] ?? '');
    $email = test_input($input['email'] ?? '');
    $gender = test_input($input['gender'] ?? '');

    if (insert($connect, $first_name, $last_name, $email, $gender, $createdBy)) {
        http_response_code(201);
        echo json_encode([
            'error' => false,
            'message' => 'Employee added successfully!'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Failed to add employee!'
        ]);
    }
}

// Handle PUT
if ($api == 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($id > 0) {
        // Determine update type based on field presence
        $isPersonalUpdate = isset($input['first_name']) || 
                          isset($input['last_name']) || 
                          isset($input['email']) || 
                          isset($input['gender']);
        
        $isWorkUpdate = isset($input['salary']) || 
                       isset($input['position']) || 
                       isset($input['size']);
        
        if ($isPersonalUpdate) {
        $first_name = test_input($input['first_name'] ?? '');
        $last_name = test_input($input['last_name'] ?? '');
        $email = test_input($input['email'] ?? '');
        $gender = test_input($input['gender'] ?? '');

        if (updatePersonalInfo($connect, $id, $first_name, $last_name, $email, $gender)) {
            echo message('Employee updated successfully!');
        } else {
            echo message('Failed to update employee!', true);
        }
    }
    elseif ($isWorkUpdate) {
        // Work info update
        $salary = test_input($input['salary'] ?? null);
        $position = test_input($input['position'] ?? null);
        $size = test_input($input['size'] ?? null);
        if (updateSalary($connect, $id, $salary, $position, $size)) {
            echo message('Employee updated successfully!');
        } else {
            echo message('Failed to update employee!', true);
        }

    } 
}
else {
    echo message('Invalid employee ID!', true);
}
}



// Handle DELETE
if ($api == 'DELETE') {
    if ($id > 0) {
        if (delete($connect, $id)) {
            echo message('Employee deleted successfully!');
        } else {
            echo message('Failed to delete employee!', true);
        }
    } else {
        echo message('Invalid employee ID!', true);
    }
}


?>