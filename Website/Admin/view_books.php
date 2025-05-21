<?php
session_start();
include '../inc/header.php';
include '../functions/db.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT Book.BookID, Book.Title, Book.ISNB, Category.CategoryName,
           COUNT(CASE WHEN bc.Status = 'available' THEN 1 END) AS available_copies
    FROM Book 
    JOIN Category ON Book.CatID = Category.CategoryID
    LEFT JOIN BookCopy bc ON Book.BookID = bc.BookID
    GROUP BY Book.BookID, Book.Title, Book.ISNB, Category.CategoryName
");
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container">
    <h2>View All Books</h2>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Book ID</th>
                    <th>Title</th>
                    <th>ISBN</th>
                    <th>Category</th>
                    <th>Available Copies</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($book = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['BookID']); ?></td>
                        <td><?php echo htmlspecialchars($book['Title']); ?></td>
                        <td><?php echo htmlspecialchars($book['ISNB']); ?></td>
                        <td><?php echo htmlspecialchars($book['CategoryName']); ?></td>
                        <td><?php echo $book['available_copies']; ?></td>
                        <td>
                            <a href="edit_book.php?id=<?= $book['BookID']; ?>" class="action-btn edit">Edit</a>
                            <a href="?delete_id=<?= $book['BookID']; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this book?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No books found.</p>
    <?php endif; ?>

    <?php
    if (isset($_GET['delete_id'])) {
        $delete_id = $_GET['delete_id'];
        // Delete related records in Have and BookCopy first due to foreign key constraints
        $stmt_delete_have = $conn->prepare("DELETE FROM Have WHERE BookID = ?");
        $stmt_delete_have->bind_param("i", $delete_id);
        $stmt_delete_have->execute();
        $stmt_delete_have->close();

        $stmt_delete_copy = $conn->prepare("DELETE FROM BookCopy WHERE BookID = ?");
        $stmt_delete_copy->bind_param("i", $delete_id);
        $stmt_delete_copy->execute();
        $stmt_delete_copy->close();

        $stmt_delete = $conn->prepare("DELETE FROM Book WHERE BookID = ?");
        $stmt_delete->bind_param("i", $delete_id);
        if ($stmt_delete->execute()) {
            echo "<p style='color:green;'>Book deleted successfully!</p>";
            header("Refresh:0");
        } else {
            echo "<p style='color:red;'>Error deleting book: " . $conn->error . "</p>";
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