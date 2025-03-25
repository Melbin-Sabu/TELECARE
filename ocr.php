<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true); // Create folder if it doesn't exist
    }

    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);

    // Run OCR on the uploaded image
    $output = shell_exec('"C:\Program Files\Tesseract-OCR\tesseract.exe" ' . escapeshellarg($target_file) . ' stdout -c tessedit_char_whitelist=0123456789');

    // Display results
    echo "<h2>Extracted Number:</h2>";
    echo "<pre>$output</pre>";
} else {
    echo "<h2>Error: No image uploaded.</h2>";
}
?>
