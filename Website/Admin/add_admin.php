<?php
session_start();
include '../inc/header.php';
include '../functions/db.php';
include '../functions/validate.php';

// Check if user_type is not set or not 'Admin'
$user_type = $_SESSION['user_type'] ?? null;
if ($user_type === null || $user_type != 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $role = $_POST['role']; // 'admin' or 'manager'
    $created_at = date('Y-m-d H:i:s');

    // Check for duplicate email
    $sql_check_email = "SELECT AdminID FROM Admin WHERE Email = ?";
    $stmt_check_email = $conn->prepare($sql_check_email);
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    $result_email = $stmt_check_email->get_result();

    if ($result_email->num_rows > 0) {
        echo "<p style='color:red;'>Error: An admin with this email already exists.</p>";
    } else {
        // Insert the new admin
        $sql = "INSERT INTO Admin (Name, Email, Password, Role, CreatedAt) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $email, $password, $role, $created_at);

        if ($stmt->execute()) {
            echo "<p style='color:green;'>Admin added successfully!</p>";
        } else {
            echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
        }
        $stmt->close();
    }
    $stmt_check_email->close();
}
?>

<div class="container">
    <h2>Admins Management</h2>

    <div class="tabs">
        <button class="tab-button active" onclick="openTab(event, 'Addadmin')">Add Admin</button>
        <a href="view_admins.php" class="tab-button">View Admins</a>
    </div>

    <div id="Addadmin" class="tab-content" style="display: block;">
        <form method="POST">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" placeholder="Admin name" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Admin email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                </select>
            </div>
            <button type="submit">Add Admin</button>
        </form>
    </div>

    <a href="dashboard.php">Home</a>
</div>
<?php
$conn->close();
include '../inc/footer.php';
?>