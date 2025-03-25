<?php
include 'db.php';

$sql = "SELECT * FROM medicines";
$result = mysqli_query($conn, $sql);
?>

<h2>Medicine List</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Quantity</th>
        <th>Price</th>
        <th>Updated At</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><?php echo $row["id"]; ?></td>
            <td><?php echo $row["name"]; ?></td>
            <td><?php echo $row["quantity"]; ?></td>
            <td><?php echo $row["price"]; ?></td>
            <td><?php echo $row["updated_at"]; ?></td>
        </tr>
    <?php } ?>
</table>
