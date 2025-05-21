<?php
session_start();
include '../inc/header.php';
include '../functions/db.php';
include '../functions/validate.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = sanitize_input($_POST['category_name']);

    $check_sql = "SELECT * FROM Category WHERE LOWER(CategoryName) = LOWER(?)";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $category_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "<p style='color:red;'>Category already exists!</p>";
    } else {
        // Insert new category
        $sql = "INSERT INTO Category (CategoryName) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $category_name);

        if ($stmt->execute()) {
            echo "<p style='color:green;'>Category added successfully!</p>";
        } else {
            echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
        }
        $stmt->close();
    }

    $check_stmt->close();
}
$conn->close();
?>

<div class="container">
    <h2>Category Management</h2>

    <div class="tabs">
        <button class="tab-button active" onclick="openTab(event, 'Addcategory')">Add Category</button>
        <a href="view_categories.php" class="tab-button">View Categories</a>
    </div>

    <div id="Addcategory" class="tab-content" style="display: block;">
        <form method="POST">
            <input type="text" name="category_name" placeholder="Category Name" required><br>
            <button type="submit">Add</button>
        </form>
    </div>

    <a href="dashboard.php">Home</a>
</div>

<?php include '../inc/footer.php'; ?>