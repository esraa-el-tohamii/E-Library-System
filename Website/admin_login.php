<?php
session_start();
include 'inc/header.php';
include 'functions/db.php';
include 'functions/validate.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT AdminID, Name, Password FROM Admin WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['AdminID'];
            $_SESSION['username'] = $user['Name'];
            $_SESSION['user_type'] = 'Admin';

            // Update LastLogin with current timestamp
            $current_time = date('Y-m-d H:i:s');
            $update_stmt = $conn->prepare("UPDATE Admin SET LastLogin = ? WHERE AdminID = ?");
            $update_stmt->bind_param("si", $current_time, $user['AdminID']);
            $update_stmt->execute();
            $update_stmt->close();

            header("Location: admin/dashboard.php");
            exit();
        } else {
            echo "<p style='color:red;'>Password not correct!</p>";
        }
    } else {
        echo "<p style='color:red;'>Email not found!</p>";
    }
    $stmt->close();
}
$conn->close();
?>

<div class="container">
    <h2>Admins login</h2>
    <form method="POST">
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" required><br>
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Password" required><br>
        </div>
        <button type="submit">Login</button>
    </form>
</div>

<?php include 'inc/footer.php'; ?>