<?php

include('includes/config.php');
include('includes/connect.php');
include('includes/function.php');
include('includes/header.php');

secure();


header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


// https://dzkrrbb.medium.com/rest-api-with-php-get-post-put-delete-8365fe092618

$query = 'SELECT * FROM `insert_data`';
$result = $connect->query($query);  
$record = $result->fetch_assoc();     

?>
<table>
    <thead>
        <tr>
            <th>id</th>
            <th>First Name<th>
            <th>Last Name<th>
            <th>Email<th>
            <th>Gender<th>
                