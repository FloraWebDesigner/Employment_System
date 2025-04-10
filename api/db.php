<?php
// api/employee.php
include('../includes/connect.php');

function fetchEmployees($connect, $id = 0) {
    $sql = 'SELECT * FROM employee_add';
    $result = [];
    
    if ($id != 0) {
        $sql .= ' WHERE id = ?';
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $query = $stmt->get_result();
    } else {
        $query = $connect->query($sql);
    }
    
    while ($row = $query->fetch_assoc()) {
        $result[] = $row;
    }
    
    return $result;
}
    
function insert($connect,$first_name, $last_name, $email, $gender, $createdBy) {
        $sql = 'INSERT INTO employee_add (first_name, last_name, email, gender, createdBy) VALUES (?, ?, ?, ?,?)';
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('sssss', $first_name, $last_name, $email, $gender, $createdBy);
        return $stmt->execute();
    }
    
function updatePersonalInfo($connect,$id, $first_name, $last_name, $email, $gender) {
        $sql = 'UPDATE employee_add SET first_name = ?, last_name = ?, email = ?, gender = ? WHERE id = ?';
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('ssssi', $first_name, $last_name, $email, $gender, $id);
        return $stmt->execute();
    }
    
function updateSalary($connect,$id, $salary, $position, $size) {
    $sql = 'UPDATE employee_add SET salary = ?, position = ?, size = ? WHERE id = ?';
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('sssi', $salary, $position, $size, $id);
    return $stmt->execute();
}
    
function delete($connect,$id) {
        $sql = 'DELETE FROM employee_add WHERE id = ?';
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }



?>