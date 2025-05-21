<?php
session_start();
include '../inc/header.php';
include '../functions/db.php';
include '../functions/validate.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

// التأكد من وجود AdminID في الجلسة
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:red;'>Error: Admin session invalid. Please log in again.</p>";
    exit();
}

// التأكد من وجود معرف الكتاب في الرابط
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p style='color:red;'>Error: Invalid book ID.</p>";
    exit();
}

$book_id = $_GET['id'];

// جلب بيانات الكتاب الحالية مع عدد النسخ المتاحة
$stmt_book = $conn->prepare("
    SELECT b.BookID, b.Title, b.ISNB, b.CatID, h.Released_Date, a.Name AS AuthorName,
           COUNT(CASE WHEN bc.Status = 'available' THEN 1 END) AS available_copies
    FROM Book b
    LEFT JOIN Have h ON b.BookID = h.BookID
    LEFT JOIN Author a ON h.AuthID = a.AuthorID
    LEFT JOIN BookCopy bc ON b.BookID = bc.BookID
    WHERE b.BookID = ?
    GROUP BY b.BookID, b.Title, b.ISNB, b.CatID, h.Released_Date, a.Name
");
$stmt_book->bind_param("i", $book_id);
$stmt_book->execute();
$result_book = $stmt_book->get_result();

if ($result_book->num_rows == 0) {
    echo "<p style='color:red;'>Error: Book not found.</p>";
    exit();
}

$book = $result_book->fetch_assoc();
$stmt_book->close();

// جلب الفئات لعرضها في القائمة المنسدلة
$sql_cats = "SELECT * FROM Category";
$result_cats = $conn->query($sql_cats);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = sanitize_input($_POST['title']);
    $isnb = sanitize_input($_POST['isnb']);
    $cat_id = $_POST['cat_id'];
    $author_name = sanitize_input($_POST['author_name']);
    $release_date = $_POST['release_date'];
    $new_available_copies = (int)$_POST['available_copies'];

    // تحقق من وجود المؤلف
    $sql_check_author = "SELECT AuthorID FROM Author WHERE Name = ?";
    $stmt_check = $conn->prepare($sql_check_author);
    $stmt_check->bind_param("s", $author_name);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $author = $result_check->fetch_assoc();
        $auth_id = $author['AuthorID'];
    } else {
        $sql_add_author = "INSERT INTO Author (Name) VALUES (?)";
        $stmt_add = $conn->prepare($sql_add_author);
        $stmt_add->bind_param("s", $author_name);
        $stmt_add->execute();
        $auth_id = $conn->insert_id;
        $stmt_add->close();
    }
    $stmt_check->close();

    // تحقق من تكرار ISNB (باستثناء الكتاب الحالي)
    $sql_check_isnb = "SELECT BookID FROM Book WHERE ISNB = ? AND BookID != ?";
    $stmt_check_isnb = $conn->prepare($sql_check_isnb);
    $stmt_check_isnb->bind_param("si", $isnb, $book_id);
    $stmt_check_isnb->execute();
    $result_isnb = $stmt_check_isnb->get_result();

    if ($result_isnb->num_rows > 0) {
        echo "<p style='color:red;'>Error: A book with this ISNB already exists.</p>";
    } else {
        // تحديث بيانات الكتاب
        $sql_update_book = "UPDATE Book SET Title = ?, ISNB = ?, CatID = ? WHERE BookID = ?";
        $stmt_update_book = $conn->prepare($sql_update_book);
        $stmt_update_book->bind_param("ssii", $title, $isnb, $cat_id, $book_id);

        if ($stmt_update_book->execute()) {
            // تحديث بيانات Have
            $sql_update_have = "UPDATE Have SET AuthID = ?, Released_Date = ? WHERE BookID = ?";
            $stmt_update_have = $conn->prepare($sql_update_have);
            $stmt_update_have->bind_param("isi", $auth_id, $release_date, $book_id);
            $stmt_update_have->execute();
            $stmt_update_have->close();

            // تحديث عدد النسخ المتاحة في BookCopy
            $current_available_copies = (int)$book['available_copies'];
            if ($new_available_copies > $current_available_copies) {
                // إضافة نسخ جديدة
                $to_add = $new_available_copies - $current_available_copies;
                for ($i = 0; $i < $to_add; $i++) {
                    $stmt_add_copy = $conn->prepare("INSERT INTO BookCopy (BookID, Status) VALUES (?, 'available')");
                    $stmt_add_copy->bind_param("i", $book_id);
                    $stmt_add_copy->execute();
                    $stmt_add_copy->close();
                }
            } elseif ($new_available_copies < $current_available_copies) {
                // حذف نسخ زائدة (فقط النسخ المتاحة)
                $to_remove = $current_available_copies - $new_available_copies;
                $stmt_remove_copy = $conn->prepare("DELETE FROM BookCopy WHERE BookID = ? AND Status = 'available' LIMIT ?");
                $stmt_remove_copy->bind_param("ii", $book_id, $to_remove);
                $stmt_remove_copy->execute();
                $stmt_remove_copy->close();
            }

            echo "<p style='color:green;'>Book updated successfully!</p>";
            // إعادة توجيه إلى صفحة العرض بعد التعديل
            echo "<script>window.location.href = 'dashboard.php';</script>";
        } else {
            echo "<p style='color:red;'>Error updating book: " . $conn->error . "</p>";
        }
        $stmt_update_book->close();
    }
    $stmt_check_isnb->close();
}
?>

<div class="container">
    <h2>Edit Book</h2>

    <div class="tabs">
        <button class="tab-button active">Edit Book</button>
        <a href="dashboard.php" class="tab-button">Back to Dashboard</a>
    </div>

    <div class="tab-content" style="display: block;">
        <form method="POST">
            <label for="title">Title:</label><br>
            <input type="text" id="title" name="title" placeholder="Book title" value="<?php echo htmlspecialchars($book['Title'] ?? ''); ?>" required><br>

            <label for="isnb">ISNB:</label><br>
            <input type="text" id="isnb" name="isnb" placeholder="ISNB number" value="<?php echo htmlspecialchars($book['ISNB'] ?? ''); ?>" required><br>

            <label for="cat_id">Category:</label><br>
            <select id="cat_id" name="cat_id" required>
                <option value="">Select Category</option>
                <?php while ($cat = $result_cats->fetch_assoc()) {
                    $selected = $cat['CategoryID'] == ($book['CatID'] ?? '') ? 'selected' : '';
                    echo "<option value='" . $cat['CategoryID'] . "' $selected>" . htmlspecialchars($cat['CategoryName']) . "</option>";
                } ?>
            </select><br>

            <label for="author_name">Author Name:</label><br>
            <input type="text" id="author_name" name="author_name" placeholder="Author Name" value="<?php echo htmlspecialchars($book['AuthorName'] ?? ''); ?>" required><br>

            <label for="release_date">Release Date:</label><br>
            <input type="date" id="release_date" name="release_date" placeholder="mm/dd/yyyy" value="<?php echo htmlspecialchars($book['Released_Date'] ?? ''); ?>" required><br>

            <label for="available_copies">Number of Copies:</label><br>
            <input type="number" id="available_copies" name="available_copies" placeholder="Number of Copies" value="<?php echo $book['available_copies'] ?? 0; ?>" min="0" required><br>

            <button type="submit">Update</button>
        </form>
    </div>

    <a href="dashboard.php">Home</a>
</div>

<?php
$conn->close();
include '../inc/footer.php';
?>