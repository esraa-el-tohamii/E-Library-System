<?php
session_start();
include '../inc/header.php';
include '../functions/db.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}
?>

<div class="container">
    <h2>Admin Dashboard</h2>
    <div class="dashboard-welcome">
        <p class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    </div>

    <div class="dashboard-menu" style="margin-top: 20px;">
        <a href="add_book.php" class="dashboard-button">Book Management</a>
        <a href="add_admin.php" class="dashboard-button">Admin Management</a>
        <a href="add_category.php" class="dashboard-button">Category Management</a>
        <a href="manage_borrowing.php" class="dashboard-button">Borrow Manage</a>
        <a href="../logout.php" class="dashboard-button logout">Logout</a>
    </div>
</div>

<?php
$conn->close();
include '../inc/footer.php';
?>