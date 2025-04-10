<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.3/flowbite.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script src="/database_flora/jquery.editable.js" type="text/javascript"></script>
	<title>Flora's Mock Data Practice</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Unna:ital,wght@0,700;1,700family=Poppins:wght@300;400;500&display=swap');
        body{
            font-family: "Poppins", sans-serif;
			min-height:100vh;
			display:flex;
			flex-direction:column;
			justify-content:space-between;
        }
		.system,
		.title{
			font-family: "Unna", serif;
			font-weight: 700;
		}
        table {
				border-collapse: collapse;
				width: 80%;
                margin:auto;
				margin-top:1rem;
			}
			th {
				background-color: #64748B;
				color:white;
				border: 1px solid #64748B;

			}
			th:hover {
				background-color:#4A5A73;
			}
			th a{
				display: block;
				text-decoration:none;
				padding: 1rem;
				color: #ffffff;
				font-weight: bold;
				font-size: 1rem;
			}
			th a i {
				margin-left: 5px;
				color: rgba(255,255,255,0.3);
			}
			td {
				padding: 10px;
				color: #636363;
				border: 1px solid #dddfe1;
			}
			tr {
				background-color: #ffffff;
			}
			tr .highlight {
				background-color: #f9fafb;
			}

			input[type=text],
			input[type=email],
			input[type=number]
			{
				padding: 0.4rem 1rem;
  				margin: 0.5rem 0;
  				box-sizing: border-box;
				border: 1px solid black;
				border-radius:0.25rem;
			}

			.pagination a {
    display: inline-block;
    padding: 8px 16px;
    margin: 0 4px;
    text-decoration: none;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.pagination a.active {
    background-color: #10B981;
    color: white;
    border: 1px solid #10B981;
}

.pagination a.disabled {
    color: #ccc;
    cursor: not-allowed;
    border-color: #eee;
    background-color: #f9f9f9;
}

.pagination a:hover:not(.active):not(.disabled) {
    background-color: #ddd;
}

.menu a:hover{
text-decoration:underline;
}

.btn{
background-color: white;
  color: black;
  border: 2px solid #04AA6D;
  transition-duration: 0.4s;
  padding: 0.4rem 2rem;
}

.btn i{
	transition-duration: 0.4s;
}

.btn:not(.disabled-btn):hover,
.btn:not(.disabled-btn):not([disabled]):hover,
.btn:hover i{
  background-color: #04AA6D;
  color: white !important;
}


.disabled-btn {
  opacity: 0.4;
  cursor: not-allowed;
}





</style>
</head>

<?php

$query = "SELECT * FROM employee_add WHERE updatedAt IS NOT NULL AND approvedBy IS NULL";
$result = $connect->query($query);
$num_rows = $result->num_rows;

?>
<body>

		<nav class="p-2 mb-4 bg-slate-500 flex flex-row items-center justify-between">

				<h1 class="text-center text-2xl text-white w-1/3 system">Welcome Employee Salary System</h1>
				<div class="w-1/3 text-white menu flex gap-5 justify-center">
				<?php if (isset($_SESSION['id']) && ($_SESSION['role'] === "manager")): ?>
    <a href="/database_flora/permission.php">Account Management</a>
    <a href="/database_flora/index.php">Salary</a>
    <a href="/database_flora/newhire.php" class="relative inline-block">
        Newhire
        <?php if ($num_rows > 0): ?>
            <span class="absolute -top-1 -right-3 bg-red-500 text-white text-[10px] rounded-full h-4 w-4 flex items-center justify-center">
                <?= $num_rows ?>
            </span>
        <?php endif; ?>
    </a>
	<a href="/database_flora/swagger-ui/dist/index.html" target="_blank">API Documentation</a>
<?php endif; ?>
				</div>
				<div class="w-1/3 flex justify-end items-center gap-3">
					<p class="text-right text-white"><?php if (isset($_SESSION['id'])) {
					    echo 'Hi '. $_SESSION['email'];
					} ?></p>
					<?php if (isset($_SESSION['id'])) {
					    echo '<a class="bg-slate-200 px-8 text-black border rounded hover:bg-slate-300" href="logout.php">Logout</a>';
					} else {
					    echo'<a class="bg-slate-200 px-8 text-black border rounded hover:bg-slate-300"" href="login.php">Login</a>';
					} ?>
				</div>
		</nav>
