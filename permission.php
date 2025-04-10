<?php

include('includes/config.php');
include('includes/connect.php');
include('includes/function.php');
include('includes/header.php');

admin_secure();


// echo $_SESSION['role'];
// echo $_SESSION['id'];

// for multiple vales: $access_size_array = explode(',', $access_size);

$query = "SELECT `id`, `email`, `role`,
          CONCAT(COALESCE(`access_gender`, 'N/A'), ',', COALESCE(`access_size`, 'N/A')) AS access 
          FROM `admin`
        WHERE `role` = 'admin'";


$result = $connect->query($query);


if ($result->num_rows > 0) {
	?>
    <div class="flex flex-col justify-start items-center">
    <h1 class="text-center text-2xl my-4 title">Account Permission</h1>
  <table class="w-1/2">
    <thead>
        <tr class="text-white">
            <th>Id</th>
            <th>Email</th>
            <th>Access</th>
           
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['email']; ?></td>
            <td><div class="flex flex-row justify-between"><?php echo $row['access']; ?>
            <div class="flex flex-row gap-3 px-2">
            <a href="edit_permission.php?edit_id=<?php echo $row['id']; ?>">
            <i class="fa-solid fa-pen-to-square text-green-500 hover:text-green-700"></i>
            </a>
            <a href="javascript:void(0);" onclick="confirmDeleteAdmin(<?php echo $row['id']; ?>, '<?php echo $row['email']; ?>')" >
            <i class="fa-solid fa-trash text-red-400 hover:text-red-600"></i>
            </a>
        </div>
        </div>
            </td>
            </tr>
            <?php endwhile; ?>
    </tbody>
    </table>

<?php
	$result->free();
}

$connect->close();

?>

</div>


<?php

include('includes/footer.php');
?>