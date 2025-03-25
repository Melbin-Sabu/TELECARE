<?php
include 'db.php';
require 'vendor/autoload.php'; // Composer required for PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $vendor = $_POST['vendor'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    $sql = "INSERT INTO medicines (name, vendor, price, stock) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdi", $name, $vendor, $price, $stock);

    if ($stmt->execute()) {
        echo "<script>alert('Medicine added successfully!');</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle Excel export
if (isset($_GET['export'])) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Medicine Name');
    $sheet->setCellValue('C1', 'Vendor');
    $sheet->setCellValue('D1', 'Price');
    $sheet->setCellValue('E1', 'Stock');
    $sheet->setCellValue('F1', 'Updated At');

    // Fetch medicine data
    $sql = "SELECT * FROM medicines";
    $result = $conn->query($sql);
    $row = 2;

    while ($data = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $data['id']);
        $sheet->setCellValue('B' . $row, $data['name']);
        $sheet->setCellValue('C' . $row, $data['vendor']);
        $sheet->setCellValue('D' . $row, $data['price']);
        $sheet->setCellValue('E' . $row, $data['stock']);
        $sheet->setCellValue('F' . $row, $data['updated_at']);
        $row++;
    }

    $writer = new Xlsx($spreadsheet);
    $file_name = "medicines.xlsx";

    // Send as download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $file_name . '"');
    header('Cache-Control: max-age=0');

    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Medicine Entry & Export</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
        form { display: inline-block; text-align: left; background: #f4f4f4; padding: 20px; border-radius: 10px; }
        input { display: block; width: 100%; margin-bottom: 10px; padding: 8px; }
        button { background: green; color: white; padding: 10px; border: none; cursor: pointer; }
        .export { background: blue; }
    </style>
</head>
<body>

    <h2>Vendor Medicine Entry</h2>
    <form action="" method="post">
        <input type="text" name="name" placeholder="Medicine Name" required>
        <input type="text" name="vendor" placeholder="Vendor Name" required>
        <input type="number" name="price" placeholder="Price" step="0.01" required>
        <input type="number" name="stock" placeholder="Stock Quantity" required>
        <button type="submit">Add Medicine</button>
    </form>

    <br><br>
    
    <h2>Admin: Export Medicines to Excel</h2>
    <a href="?export=true">
        <button class="export">Download Updated Medicines</button>
    </a>

</body>
</html>
