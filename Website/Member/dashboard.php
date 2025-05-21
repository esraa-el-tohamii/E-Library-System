<?php
session_start();
include '../inc/header.php';
include '../functions/db.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'Member') {
    header("Location: ../member_login.php");
    exit();
}
?>

<div class="container">
    <h2>Member Dashboard</h2>
    <p>Welcome, <?php echo $_SESSION['username']; ?>!</p>
    <a href="view_books.php">Books</a><br>
    <a href="../logout.php">Logout</a>
</div>

<?php include '../inc/footer.php'; ?>