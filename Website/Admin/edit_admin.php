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

$current_admin_id = $_SESSION['user_id'];

// التأكد من وجود معرف الـ Admin في الرابط
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p style='color:red;'>Error: Invalid admin ID.</p>";
    exit();
}

$admin_id = $_GET['id'];

// منع تعديل الـ Admin الحالي بنفسه
if ($admin_id == $current_admin_id) {
    echo "<p style='color:red;'>Error: You cannot edit your own account.</p>";
    exit();
}

// جلب بيانات الـ Admin الحالية
$stmt_admin = $conn->prepare("SELECT AdminID, Name, Email FROM Admin WHERE AdminID = ?");
$stmt_admin->bind_param("i", $admin_id);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();

if ($result_admin->num_rows == 0) {
    echo "<p style='color:red;'>Error: Admin not found.</p>";
    exit();
}

$admin = $result_admin->fetch_assoc();
$stmt_admin->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // لن نطلب كلمة المرور دائمًا، فقط إذا أراد التغيير

    // إذا تم إدخال كلمة مرور جديدة، قم بتخزينها مشفرة
    $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : $admin['Password'];

    // تحقق من تكرار البريد الإلكتروني (باستثناء الـ Admin الحالي)
    $sql_check = "SELECT AdminID FROM Admin WHERE Email = ? AND AdminID != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $email, $admin_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        echo "<p style='color:red;'>Error: An admin with this email already exists.</p>";
    } else {
        // تحديث بيانات الـ Admin
        $sql_update = "UPDATE Admin SET Username = ?, Email = ?, Password = ? WHERE AdminID = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssi", $username, $email, $hashed_password, $admin_id);

        if ($stmt_update->execute()) {
            echo "<p style='color:green;'>Admin updated successfully!</p>";
            // إعادة توجيه إلى صفحة العرض بعد التعديل
            echo "<script>window.location.href = 'view_admins.php';</script>";
        } else {
            echo "<p style='color:red;'>Error updating admin: " . $conn->error . "</p>";
        }
        $stmt_update->close();
    }
    $stmt_check->close();
}
?>

<div class="container">
    <h2>Edit Admin</h2>

    <div class="tabs">
        <button class="tab-button active">Edit Admin</button>
        <a href="dashboard.php" class="tab-button">Back to Dashboard</a>
    </div>

    <div class="tab-content" style="display: block;">
        <form method="POST">
            <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($admin['Name']); ?>" required><br>
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($admin['Email']); ?>" required><br>
            <input type="password" name="password" placeholder="Password (leave blank to keep current)"><br>
            <button type="submit">Update</button>
        </form>
    </div>

    <a href="dashboard.php">Home</a>
</div>

<?php
$conn->close();
include '../inc/footer.php';
?>