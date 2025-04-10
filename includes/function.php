<?php

function secure(){
    //  if the login account exists, can go to index
    if(!isset($_SESSION['id'])){

    header('Location: login.php');
        die();

    }
}

function admin_secure(){

    if(!isset($_SESSION['id'])||($_SESSION['role']!=='manager')){

    header('Location: permission.php');
        die();

    }
}


function data_entry_secure(){

    if(!isset($_SESSION['id'])||($_SESSION['role']!=='data_entry')){

    header('Location: add_salary.php');
        die();

    }
}