<?php include 'inc/header.php'; ?>
<?php include 'functions/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $author_name = trim($_POST['author_name']);
    $category_id = $_POST['category_id'];
    $pub_date = $_POST['pub_date'];

    $stmt = $conn->prepare("SELECT AuthorID FROM author WHERE Name = ?");
    $stmt->bind_param("s", $author_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        $stmt->bind_result($author_id);
        $stmt->fetch();
    } else {

        $insertAuthor = $conn->prepare("INSERT INTO author (Name) VALUES (?)");
        $insertAuthor->bind_param("s", $author_name);
        $insertAuthor->execute();
        $author_id = $insertAuthor->insert_id;
        $insertAuthor->close();
    }
    $stmt->close();

    $insertBook = $conn->prepare("INSERT INTO book (Title, CatID, Publication_Date) VALUES (?, ?, ?)");
    $insertBook->bind_param("sis", $title, $category_id, $pub_date);

    if ($insertBook->execute()) {
        $book_id = $insertBook->insert_id;

        $have = $conn->prepare("INSERT INTO have (BookID, AuthID) VALUES (?, ?)");
        $have->bind_param("ii", $book_id, $author_id);
        $have->execute();
        $have->close();

        echo "<p style='color:green;'>Book added successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
    }
    $insertBook->close();
}
?>

<div class="container">
    <h2>Add new book</h2>
    <form method="POST">
        <input type="text" name="title" placeholder="Book title" required><br>
        <input type="text" name="author_name" placeholder="Author Name" required><br>

        <select name="category_id" required>
            <option value="">Select Category</option>
            <?php
            $cat_result = $conn->query("SELECT CategoryID, CategoryName FROM category");
            while ($row = $cat_result->fetch_assoc()) {
                echo "<option value='" . $row['CategoryID'] . "'>" . htmlspecialchars($row['CategoryName']) . "</option>";
            }
            ?>
        </select><br>

        <input type="date" name="pub_date" required><br>
        <button type="submit">Add</button>
    </form>
</div>

<?php include 'inc/footer.php'; ?>