<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {
    $uploadDir = "C:/xampp/htdocs/uploads/";
    $outputDir = "C:/xampp/htdocs/output/";

    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!file_exists($outputDir)) mkdir($outputDir, 0777, true);

    $imageFile = $uploadDir . basename($_FILES["image"]["name"]);
    $imageType = strtolower(pathinfo($imageFile, PATHINFO_EXTENSION));

    // Convert WebP to PNG if needed
    if ($imageType == "webp") {
        $img = imagecreatefromwebp($_FILES["image"]["tmp_name"]);
        $imageFile = $uploadDir . "converted.png";
        imagepng($img, $imageFile);
        imagedestroy($img);
    } else {
        move_uploaded_file($_FILES["image"]["tmp_name"], $imageFile);
    }

    // Tesseract Path
    $tesseractPath = '"C:\Program Files\Tesseract-OCR\tesseract.exe"';
    $outputFile = $outputDir . "result";

    // Improved OCR settings
    $command = "$tesseractPath \"$imageFile\" \"$outputFile\" --psm 6 -c tessedit_char_whitelist=0123456789.";
    shell_exec($command);

    $textFile = $outputFile . ".txt";
    $extractedText = file_exists($textFile) ? file_get_contents($textFile) : "❌ OCR failed!";

    // Extract only numbers from text
    preg_match_all('/\d+/', $extractedText, $matches);
    $numbers = implode(", ", $matches[0]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCR Number Extraction</title>
</head>
<body>

    <h2>Upload an Image for OCR</h2>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="image" accept="image/*" required>
        <br><br>
        <input type="submit" value="Upload & Extract Numbers">
    </form>

    <?php if (!empty($numbers)): ?>
        <h2>Extracted Numbers:</h2>
        <pre><?php echo htmlspecialchars($numbers); ?></pre>
    <?php endif; ?>

</body>
</html>
