<?php
session_start();
include '../inc/header.php';
include '/nctions/db.php';
if (!isset($_SESSION['member_id'])) {
    header("Location: ../login_member.php");
    exit();
}
?>

<h2>Welcome, <?php echo $_SESSION['member_name']; ?>!</h2>
<p>This is the member dashboard.</p>
<a href="../logout.php">Logout</a>
<?php include '../inc/footer.php'; ?>