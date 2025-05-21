<?php
session_start();
include '../inc/header.php';
include '../functions/db.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

$current_admin_id = $_SESSION['user_id'] ?? null;

$stmt = $conn->prepare("SELECT AdminID, Name, Email,Role FROM Admin");
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container">
    <h2>View All Admins</h2>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Admin ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($admin = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['AdminID']); ?></td>
                        <td><?php echo htmlspecialchars($admin['Name']); ?></td>
                        <td><?php echo htmlspecialchars($admin['Email']); ?></td>
                        <td><?php echo htmlspecialchars($admin['Role']); ?></td>
                        <td>
                            <a href="edit_admin.php?id=<?= $admin['AdminID']; ?>" class="action-btn edit">Edit</a>
                            <a href="?delete_id=<?= $admin['AdminID']; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this admin?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No other admins found.</p>
    <?php endif; ?>

    <?php
    if (isset($_GET['delete_id'])) {
        $delete_id = $_GET['delete_id'];
        $stmt_delete = $conn->prepare("DELETE FROM Admin WHERE AdminID = ?");
        $stmt_delete->bind_param("i", $delete_id);
        if ($stmt_delete->execute()) {
            echo "<p style='color:green;'>Admin deleted successfully!</p>";
            header("Refresh:0");
        } else {
            echo "<p style='color:red;'>Error deleting admin: " . $conn->error . "</p>";
        }
        $stmt_delete->close();
    }
    ?>

    <a href="dashboard.php">Back to Home</a>
</div>

<?php
$stmt->close();
$conn->close();
include '../inc/footer.php';
?>