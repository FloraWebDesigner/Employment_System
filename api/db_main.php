<?php
// api/employee.php
include('../includes/connect.php');

function fetchEmployees($connect, $id = 0) {
    $sql = 'SELECT DISTINCT * FROM mock_data';
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
    
function insert($connect,$first_name, $last_name, $email, $gender) {
        $sql = 'INSERT INTO mock_data (first_name, last_name, email, gender, salary,position, top_size, date_started) VALUES (?, ?, ?, ?,"0","please update","please update","please update")';
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('ssss', $first_name, $last_name, $email, $gender);
        return $stmt->execute();
    }
    
// function update($connect,$id, $first_name, $last_name, $email, $gender) {
//         $sql = 'UPDATE mock_data SET first_name = ?, last_name = ?, email = ?, gender = ? WHERE id = ?';
//         $stmt = $connect->prepare($sql);
//         $stmt->bind_param('ssssi', $first_name, $last_name, $email, $gender, $id);
//         return $stmt->execute();
//     }
    
// function delete($connect,$id) {
//         $sql = 'DELETE FROM mock_data WHERE id = ?';
//         $stmt = $connect->prepare($sql);
//         $stmt->bind_param('i', $id);
//         return $stmt->execute();
//     }



?>