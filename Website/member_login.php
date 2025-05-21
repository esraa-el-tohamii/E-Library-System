<?php
session_start();
include 'inc/header.php';
include 'functions/db.php';
include 'functions/validate.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT MemberID, Name, Password FROM Member WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['MemberID'];
            $_SESSION['username'] = $user['Name'];
            $_SESSION['user_type'] = 'Member';
            header("Location: member/dashboard.php");
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
    <h2>Login</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
    </form>
    <p>Don't have any account? <a href="register.php">Register now</a></p>
</div>

<?php include 'inc/footer.php'; ?>