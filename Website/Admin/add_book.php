<?php
session_start();
include '../inc/header.php';
include '../functions/db.php';
include '../functions/validate.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'Admin') {
    header("Location: ../admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = sanitize_input($_POST['title']);
    $isnb = sanitize_input($_POST['isnb']);
    $cat_id = $_POST['cat_id'];
    $author_name = sanitize_input($_POST['author_name']);
    $release_date = $_POST['release_date'];
    $copies_number = (int)$_POST['copies_number'];

    if ($copies_number <= 0) {
        echo "<p style='color:red;'>Error: Number of copies must be greater than 0.</p>";
    } else {
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

        $sql_check_isnb = "SELECT BookID FROM Book WHERE ISNB = ?";
        $stmt_check_isnb = $conn->prepare($sql_check_isnb);
        $stmt_check_isnb->bind_param("s", $isnb);
        $stmt_check_isnb->execute();
        $result_isnb = $stmt_check_isnb->get_result();

        if ($result_isnb->num_rows > 0) {
            echo "<p style='color:red;'>Error: A book with this ISNB already exists.</p>";
        } else {
            $sql_book = "INSERT INTO Book (Title, ISNB, CatID) VALUES (?, ?, ?)";
            $stmt_book = $conn->prepare($sql_book);
            $stmt_book->bind_param("ssi", $title, $isnb, $cat_id);

            if ($stmt_book->execute()) {
                $book_id = $conn->insert_id;

                $sql_have = "INSERT INTO Have (BookID, AuthID, Released_Date) VALUES (?, ?, ?)";
                $stmt_have = $conn->prepare($sql_have);
                $stmt_have->bind_param("iis", $book_id, $auth_id, $release_date);
                $stmt_have->execute();
                $stmt_have->close();

                $sql_copy = "INSERT INTO BookCopy (BookID) VALUES (?)";
                $stmt_copy = $conn->prepare($sql_copy);
                $stmt_copy->bind_param("i", $book_id);
                for ($i = 0; $i < $copies_number; $i++) {
                    $stmt_copy->execute();
                }
                $stmt_copy->close();

                echo "<p style='color:green;'>Book added successfully with $copies_number copies!</p>";
            } else {
                echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
            }
            $stmt_book->close();
        }
        $stmt_check_isnb->close();
    }
}

$sql_cats = "SELECT * FROM Category";
$result_cats = $conn->query($sql_cats);
?>

<div class="container">
    <h2>Book Management</h2>

    <div class="tabs">
        <button class="tab-button active" onclick="openTab(event, 'Addbook')">Add Book</button>
        <a href="view_books.php" class="tab-button">View Books</a>
    </div>

    <div id="Addbook" class="tab-content" style="display: block;">
        <form method="POST">
            <div class="form-group">
                <label for="title">Book Title:</label>
                <input type="text" id="title" name="title" placeholder="Book title" required><br>
            </div>
            <div class="form-group">
                <label for="isnb">ISBN Number:</label>
                <input type="text" id="isnb" name="isnb" placeholder="ISNB number" required><br>
            </div>
            <div class="form-group">
                <label for="cat_id">Category:</label>
                <select id="cat_id" name="cat_id" required>
                    <option value="">Select Category</option>
                    <?php while ($cat = $result_cats->fetch_assoc()) {
                        echo "<option value='" . $cat['CategoryID'] . "'>" . $cat['CategoryName'] . "</option>";
                    } ?>
                </select><br>
            </div>
            <div class="form-group">
                <label for="author_name">Author Name:</label>
                <input type="text" id="author_name" name="author_name" placeholder="Author name" required><br>
            </div>
            <div class="form-group">
                <label for="release_date">Release Date:</label>
                <input type="date" id="release_date" name="release_date" required><br>
            </div>
            <div class="form-group">
                <label for="copies_number">Number of Copies:</label>
                <input type="number" id="copies_number" name="copies_number" placeholder="Number of copies" min="1" value="1" required><br>
            </div>
            <button type="submit">Add</button>
        </form>
    </div>

    <a href="dashboard.php">Home</a>
</div>

<?php
$conn->close();
include '../inc/footer.php';
?>