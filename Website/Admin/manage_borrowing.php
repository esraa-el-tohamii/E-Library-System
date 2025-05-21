<?php
session_start();
include '../inc/header.php';
include '../functions/db.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['return'])) {
    $borrow_date = $_POST['borrow_date'];
    $return_date = $_POST['return_date'];
    $stmt_return = $conn->prepare("SELECT CopyID FROM Borrowing WHERE BorrowDate = ? AND ReturnDate = ? AND ReturnDate IS NOT NULL");
    $stmt_return->bind_param("ss", $borrow_date, $return_date);
    $stmt_return->execute();
    $result_return = $stmt_return->get_result();
    while ($row = $result_return->fetch_assoc()) {
        $copy_id = $row['CopyID'];
        $stmt_update_borrow = $conn->prepare("UPDATE Borrowing SET ReturnDate = NULL WHERE CopyID = ? AND BorrowDate = ? AND ReturnDate = ?");
        $stmt_update_borrow->bind_param("iss", $copy_id, $borrow_date, $return_date);
        $stmt_update_borrow->execute();
        $stmt_update_borrow->close();
        $stmt_update_copy = $conn->prepare("UPDATE BookCopy SET Status = 'available' WHERE CopyID = ?");
        $stmt_update_copy->bind_param("i", $copy_id);
        $stmt_update_copy->execute();
        $stmt_update_copy->close();
    }
    $stmt_return->close();
    echo "<p style='color:green;'>Return book happened successfully!</p>";
    echo "<script>window.location.href = window.location.href;</script>";
}

$sql = "
    SELECT m.Name AS member_name, GROUP_CONCAT(DISTINCT b.Title SEPARATOR ', ') AS book_titles, 
           br.BorrowDate, br.ReturnDate, COUNT(*) AS borrowed_copies
    FROM Borrowing br
    JOIN Member m ON br.MemberID = m.MemberID
    JOIN BookCopy bc ON br.CopyID = bc.CopyID
    JOIN Book b ON bc.BookID = b.BookID
    WHERE br.ReturnDate IS NOT NULL
    GROUP BY m.Name, br.BorrowDate, br.ReturnDate";
$result = $conn->query($sql);
?>

<div class="container">
    <h2>Borrow Management</h2>
    <?php
    if ($result->num_rows > 0) {
        echo "<table border='1'><tr><th>Member name</th><th>Books</th><th>Borrow date</th><th>Expected Return Date</th><th>Total Copies</th><th>Details</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . htmlspecialchars($row['member_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['book_titles']) . "</td>";
            echo "<td>" . htmlspecialchars($row['BorrowDate']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ReturnDate']) . "</td>";
            echo "<td>" . $row['borrowed_copies'] . "</td>";
            echo "<td><form method='POST'><input type='hidden' name='borrow_date' value='" . $row['BorrowDate'] . "'><input type='hidden' name='return_date' value='" . $row['ReturnDate'] . "'><button type='submit' name='return'>Return</button></form></td></tr>";
        }
        echo "</table>";
    } else {
        echo "No borrows yet.";
    }
    $conn->close();
    ?>
    <a href="dashboard.php">Home</a>
</div>

<?php include '../inc/footer.php'; ?>