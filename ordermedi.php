<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "telecare+";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Handle cart addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $medicine_id = $_POST['medicine_id'];
    $sql = "INSERT INTO cart (user_id, medicine_id) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $medicine_id);
    $stmt->execute();
    $stmt->close();
}

// Handle search
$search_term = "";
$search_results = [];
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search'])) {
    $search_term = trim($_GET['search']);
    $sql = "SELECT * FROM medicines WHERE name LIKE ? OR price LIKE ? OR company LIKE ? ORDER BY name";
    $stmt = $conn->prepare($sql);
    $search_param = "%" . $search_term . "%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $search_results = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Medicines - Telecare+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .autocomplete-suggestions {
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            width: 100%;
            max-height: 150px;
            overflow-y: auto;
            z-index: 1000;
        }
        .autocomplete-suggestion {
            padding: 8px;
            cursor: pointer;
        }
        .autocomplete-suggestion:hover {
            background-color: rgb(60, 241, 20);
        }
        .sidebar {
            width: 250px;
            background: #4CAF50;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px;
            color: white;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .sidebar a {
            display: block;
            padding: 10px;
            background: white;
            color: #4CAF50;
            text-align: center;
            font-weight: bold;
            border-radius: 8px;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background: #388E3C;
            color: white;
        }
        .main-content {
            margin-left: 270px;
            padding: 20px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="sidebar">
        <h2 class="text-xl font-bold text-white">TELECARE+</h2>
        <a href="#">📤 Upload Prescription</a>
        <a href="healthmonito.php">📊 Health Monitoring</a>
        <a href="ordermedi.php">🛒 Order Medicines</a>
        <a href="cart.php">🛍 Cart</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="main-content">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Search Medicines</h1>

        <form method="GET" class="mb-6 relative">
            <input type="text" id="search-box" name="search" value="<?php echo htmlspecialchars($search_term); ?>" 
                placeholder="Enter medicine name, price, or company" 
                class="w-full px-4 py-2 border rounded bg-green-100 focus:bg-green-200 focus:outline-none focus:ring-2 focus:ring-green-500">
            <div id="autocomplete-results" class="autocomplete-suggestions hidden"></div>
            <button type="submit" 
                class="mt-2 bg-green-400 text-white px-6 py-2 rounded hover:bg-green-500">
                Search
            </button>
        </form>

        <?php if (!empty($search_results)): ?>
            <div class="result-container p-6 bg-white rounded-lg shadow-md">
                <h2 class="text-xl font-bold text-green-700 mb-4">Search Results for "<?php echo htmlspecialchars($search_term); ?>"</h2>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="px-4 py-2 text-left">Medicine Name</th>
                            <th class="px-4 py-2 text-left">Expiry Date</th>
                            <th class="px-4 py-2 text-left">Price</th>
                            <th class="px-4 py-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $row): ?>
                            <tr class="border-t hover:bg-green-50">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['expiry_date']); ?></td>
                                <td class="px-4 py-2">$<?php echo number_format($row['price'], 2); ?></td>
                                <td class="px-4 py-2 text-center">
                                    <form method="POST">
                                        <input type="hidden" name="medicine_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="add_to_cart" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                            Add to Cart
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($search_term !== ""): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">No results found!</strong>
                <span class="block sm:inline"> No medicines match "<?php echo htmlspecialchars($search_term); ?>".</span>
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
            $("#search-box").on("input", function() {
                let query = $(this).val();
                if (query.length > 0) {
                    $.ajax({
                        url: "autocomplete.php",
                        method: "GET",
                        data: { search: query },
                        success: function(data) {
                            let results = JSON.parse(data);
                            let suggestionBox = $("#autocomplete-results");
                            suggestionBox.empty().removeClass("hidden");

                            if (results.length === 0) {
                                suggestionBox.append("<div class='autocomplete-suggestion'>No matches found</div>");
                            } else {
                                results.forEach(item => {
                                    let suggestion = <div class='autocomplete-suggestion' data-value='${item.name}'>${item.name}</div>;
                                    suggestionBox.append(suggestion);
                                });
                            }

                            $(".autocomplete-suggestion").on("click", function() {
                                $("#search-box").val($(this).data("value"));
                                suggestionBox.addClass("hidden");
                                $("#search-box").closest("form").submit(); // Submit form when suggestion clicked
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error("Autocomplete error:", error);
                        }
                    });
                } else {
                    $("#autocomplete-results").empty().addClass("hidden");
                }
            });
        });
    </script>
</body>
</html>
