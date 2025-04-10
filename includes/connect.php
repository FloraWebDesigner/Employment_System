<?php
// includes/connect.php
if (!defined('DB_HOST')) {
    $env = file(__DIR__.'/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($env as $value) {
        $value = explode('=', $value);  
        if (!defined($value[0])) {
            define($value[0], $value[1]);
        }
    }

    $connect = new mysqli(
        DB_HOST, 
        DB_USERNAME, 
        DB_PASSWORD, 
        DB_DATABASE
    );

    if ($connect->connect_error) {
        die("Connection failed: " . $connect->connect_error);
    }
}
?>