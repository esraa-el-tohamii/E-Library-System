<?php
session_start();
include '../inc/header.php';
include '../functions/db.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

$stmt = $conn->prepare("SELECT CategoryID, CategoryName FROM Category");
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container">
    <h2>View All Categories</h2>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Category ID</th>
                    <th>Category Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($category = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['CategoryID']); ?></td>
                        <td><?php echo htmlspecialchars($category['CategoryName']); ?></td>
                        <td>
                            <a href="edit_category.php?id=<?= $category['CategoryID']; ?>" class="action-btn edit">Edit</a>
                            <a href="?delete_id=<?= $category['CategoryID']; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No categories found.</p>
    <?php endif; ?>

    <?php
    if (isset($_GET['delete_id'])) {
        $delete_id = $_GET['delete_id'];
        $stmt_delete = $conn->prepare("DELETE FROM Category WHERE CategoryID = ?");
        $stmt_delete->bind_param("i", $delete_id);
        if ($stmt_delete->execute()) {
            echo "<p style='color:green;'>Category deleted successfully!</p>";
            header("Refresh:0");
        } else {
            echo "<p style='color:red;'>Error deleting category: " . $conn->error . "</p>";
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