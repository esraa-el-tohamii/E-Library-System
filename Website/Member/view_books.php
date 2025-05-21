<?php
session_start();
include '../inc/header.php';
include '../functions/db.php';
include '../functions/validate.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'Member') {
    header("Location: ../member_login.php");
    exit();
}

$admin_id = null;
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];
} else {
    $stmt_admin = $conn->prepare("SELECT AdminID FROM Admin LIMIT 1");
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    if ($result_admin->num_rows > 0) {
        $admin = $result_admin->fetch_assoc();
        $admin_id = $admin['AdminID'];
    } else {
        echo "<p style='color:red;'>Error: No admin available to process the borrowing.</p>";
        exit();
    }
    $stmt_admin->close();
}

$member_id = $_SESSION['user_id'] ?? null;
if (!$member_id) {
    echo "<p style='color:red;'>Error: Member session invalid. Please log in again.</p>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['borrow'])) {
    $copy_id = $_POST['copy_id'];
    $requested_copies = (int)$_POST['requested_copies'];
    $expected_return_date = $_POST['expected_return_date'];
    $borrow_date = date('Y-m-d');
    if ($expected_return_date <= $borrow_date) {
        echo "<p style='color:red;'>Error: Return date must be in the future.</p>";
    } else {
        $sql_check = "SELECT COUNT(*) AS available FROM BookCopy WHERE BookID = (SELECT BookID FROM BookCopy WHERE CopyID = ?) AND Status = 'available'";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $copy_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $available = $result_check->fetch_assoc()['available'];
        $stmt_check->close();
        if ($requested_copies <= 0 || $requested_copies > $available) {
            echo "<p style='color:red;'>Error: Invalid number of copies requested.</p>";
        } else {
            $stmt_borrow = $conn->prepare("SELECT CopyID FROM BookCopy WHERE BookID = (SELECT BookID FROM BookCopy WHERE CopyID = ?) AND Status = 'available' LIMIT ?");
            $stmt_borrow->bind_param("ii", $copy_id, $requested_copies);
            $stmt_borrow->execute();
            $result_borrow = $stmt_borrow->get_result();
            while ($row = $result_borrow->fetch_assoc()) {
                $copy_id = $row['CopyID'];
                $sql_insert = "INSERT INTO Borrowing (BorrowDate, MemberID, CopyID, AdminID, ReturnDate) VALUES (?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("siiis", $borrow_date, $member_id, $copy_id, $admin_id, $expected_return_date);
                $stmt_insert->execute();
                $stmt_insert->close();
                $sql_update = "UPDATE BookCopy SET Status = 'borrowed' WHERE CopyID = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $copy_id);
                $stmt_update->execute();
                $stmt_update->close();
            }
            $stmt_borrow->close();
            echo "<p style='color:green;'>Borrow happened successfully!</p>";
            echo "<script>window.location.href = window.location.href;</script>";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['return'])) {
    $borrow_date = $_POST['borrow_date'];
    $return_date = $_POST['return_date'];
    $stmt_return = $conn->prepare("SELECT CopyID FROM Borrowing WHERE MemberID = ? AND BorrowDate = ? AND ReturnDate = ? AND ReturnDate IS NOT NULL");
    $stmt_return->bind_param("iss", $member_id, $borrow_date, $return_date);
    $stmt_return->execute();
    $result_return = $stmt_return->get_result();
    while ($row = $result_return->fetch_assoc()) {
        $copy_id = $row['CopyID'];
        $stmt_update_borrow = $conn->prepare("UPDATE Borrowing SET ReturnDate = NULL WHERE MemberID = ? AND CopyID = ? AND BorrowDate = ? AND ReturnDate = ?");
        $stmt_update_borrow->bind_param("iiss", $member_id, $copy_id, $borrow_date, $return_date);
        $stmt_update_borrow->execute();
        $stmt_update_borrow->close();
        $stmt_update_copy = $conn->prepare("UPDATE BookCopy SET Status = 'available' WHERE CopyID = ?");
        $stmt_update_copy->bind_param("i", $copy_id);
        $stmt_update_copy->execute();
        $stmt_update_copy->close();
    }
    $stmt_return->close();
    echo "<p style='color:green;'>Return happened successfully!</p>";
    echo "<script>window.location.href = window.location.href;</script>";
}

$sql = "
    SELECT b.BookID, b.Title, a.Name AS author_name, c.CategoryName AS category_name, 
           COUNT(CASE WHEN bc.Status = 'available' THEN 1 END) AS available_copies,
           MIN(CASE WHEN bc.Status = 'available' THEN bc.CopyID END) AS available_copy_id
    FROM Book b
    JOIN Have h ON b.BookID = h.BookID 
    JOIN Author a ON h.AuthID = a.AuthorID 
    JOIN Category c ON b.CatID = c.CategoryID 
    LEFT JOIN BookCopy bc ON b.BookID = bc.BookID 
    GROUP BY b.BookID, b.Title, a.Name, c.CategoryName";
$result = $conn->query($sql);

$sql_borrowings = "
    SELECT m.Name AS member_name, GROUP_CONCAT(b.Title SEPARATOR ', ') AS book_titles, 
           br.BorrowDate, br.ReturnDate, COUNT(*) AS borrowed_copies
    FROM Borrowing br
    JOIN Member m ON br.MemberID = m.MemberID
    JOIN BookCopy bc ON br.CopyID = bc.CopyID
    JOIN Book b ON bc.BookID = b.BookID
    WHERE br.MemberID = ? AND br.ReturnDate IS NOT NULL
    GROUP BY m.Name, br.BorrowDate, br.ReturnDate";
$stmt_borrowings = $conn->prepare($sql_borrowings);
$stmt_borrowings->bind_param("i", $member_id);
$stmt_borrowings->execute();
$result_borrowings = $stmt_borrowings->get_result();

// تحديد تاريخ افتراضي (بعد 7 أيام من اليوم)
$default_return_date = date('Y-m-d', strtotime('+7 days'));
?>

<div class="container">
    <h2>Book list</h2>
    <?php
    if ($result->num_rows > 0) {
        echo "<table class='book-table'>";
        echo "<tr><th>Book title</th><th>Author</th><th>Category</th><th>Available Copies</th><th>Status</th><th>Details</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $is_available = $row['available_copies'] > 0;
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row["Title"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["author_name"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["category_name"]) . "</td>";
            echo "<td>" . $row['available_copies'] . "</td>";
            echo "<td class='status " . ($is_available ? "status-available" : "status-not-available") . "'>" . ($is_available ? "available" : "not available") . "</td>";
            echo "<td>";
            echo "<form method='POST' class='borrow-form'>";
            echo "<input type='hidden' name='copy_id' value='" . ($row["available_copy_id"] ?? '') . "'>";
            echo "<div class='form-group'>";
            echo "<label for='requested_copies'>Copy numbers you want:</label>";
            echo "<input type='number' name='requested_copies' id='requested_copies' min='1' max='" . $row['available_copies'] . "' value='1' class='copy-input' required>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='expected_return_date'>Expected Return Date:</label>";
            echo "<input type='date' name='expected_return_date' id='expected_return_date' class='date-input' value='$default_return_date' required>";
            echo "</div>";
            echo "<button type='submit' name='borrow' " . ($is_available ? "" : "disabled") . " class='borrow-btn " . ($is_available ? "borrow-available" : "borrow-unavailable") . "'>Borrow</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No books yet.";
    }
    ?>
    <?php
    $stmt_borrowings->close();
    $conn->close();
    ?>
    <a href="../logout.php" class="logout-link">Logout</a>
</div>

<?php include '../inc/footer.php'; ?>