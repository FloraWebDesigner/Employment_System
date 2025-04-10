<?php

include('includes/config.php');
include('includes/connect.php');
include('includes/function.php');

if(isset($_POST['email']))
// submit the POST form
{
    $query = 'SELECT *
    FROM `admin`
    WHERE email = "'.$_POST['email'].'"
    AND password = "'.md5($_POST['password']).'"
    LIMIT 1';

    $result=mysqli_query($connect,$query);

    if(mysqli_num_rows($result)){

        $record=mysqli_fetch_assoc($result);

        $_SESSION['id']=$record['id'];
        $_SESSION['email']=$_POST['email'];
        $_SESSION['role']=$record['role'];
        $_SESSION['access_gender']=$record['access_gender'];
        $_SESSION['access_size']=$record['access_size'];

        // echo  $_SESSION['access_gender'];
        // echo $_SESSION['access_size'];

        if($record['role']==='manager'){
            header('Location: permission.php');
        }
        else if($record['role']==='data_entry'){
            header('Location: add_salary.php');
        }
        else{
            header('Location: index.php');
        }
        die();
    }
}

include('includes/header.php');

?>
        <div class="w-1/4 mx-auto mt-20">
            <form method="post" class="max-w-md mx-auto p-4 bg-white rounded shadow-md">
            <h2 class="text-center text-2xl mb-4 title">Admin Login</h2>
            <div class="mb-4 px-8">
                <label for="email" class="form-label block text-sm font-semibold">Email</label>
                <input type="text" name="email" class="form-control w-full">
            </div>
            <div class="mb-6 px-8">
                <label for="password"class="form-label block text-sm font-semibold">Password</label>
                <input type="text" name="password" class="form-control w-full">
            </div>
            <div class="flex justify-center mb-6 px-8">
            <button type="submit" class="bg-slate-200 px-8 py-2 text-black border border-gray-700 rounded hover:bg-slate-300 w-full" name="login">Login</button>
            </div>
            </form>
        </div>

<?php
include('includes/footer.php');
?>