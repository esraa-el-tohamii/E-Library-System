<?php
session_start();
include '../inc/header.php';
include '../functions/db.php';
include '../functions/validate.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

// التأكد من وجود AdminID في الجلسة
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:red;'>Error: Admin session invalid. Please log in again.</p>";
    exit();
}

// التأكد من وجود معرف الفئة في الرابط
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p style='color:red;'>Error: Invalid category ID.</p>";
    exit();
}

$category_id = $_GET['id'];

// جلب بيانات الفئة الحالية
$stmt_category = $conn->prepare("SELECT CategoryID, CategoryName FROM Category WHERE CategoryID = ?");
$stmt_category->bind_param("i", $category_id);
$stmt_category->execute();
$result_category = $stmt_category->get_result();

if ($result_category->num_rows == 0) {
    echo "<p style='color:red;'>Error: Category not found.</p>";
    exit();
}

$category = $result_category->fetch_assoc();
$stmt_category->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = sanitize_input($_POST['category_name']);

    // تحقق من تكرار اسم الفئة (باستثناء الفئة الحالية)
    $sql_check = "SELECT CategoryID FROM Category WHERE CategoryName = ? AND CategoryID != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $category_name, $category_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        echo "<p style='color:red;'>Error: A category with this name already exists.</p>";
    } else {
        // تحديث بيانات الفئة
        $sql_update = "UPDATE Category SET CategoryName = ? WHERE CategoryID = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $category_name, $category_id);

        if ($stmt_update->execute()) {
            echo "<p style='color:green;'>Category updated successfully!</p>";
            // إعادة توجيه إلى صفحة العرض بعد التعديل
            echo "<script>window.location.href = 'view_categories.php';</script>";
        } else {
            echo "<p style='color:red;'>Error updating category: " . $conn->error . "</p>";
        }
        $stmt_update->close();
    }
    $stmt_check->close();
}
?>

<div class="container">
    <h2>Edit Category</h2>

    <div class="tabs">
        <button class="tab-button active">Edit Category</button>
        <a href="dashboard.php" class="tab-button">Back to Dashboard</a>
    </div>

    <div class="tab-content" style="display: block;">
        <form method="POST">
            <input type="text" name="category_name" placeholder="Category name" value="<?php echo htmlspecialchars($category['CategoryName']); ?>" required><br>
            <button type="submit">Update</button>
        </form>
    </div>

    <a href="dashboard.php">Home</a>
</div>

<?php
$conn->close();
include '../inc/footer.php';
?>