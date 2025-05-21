<?php include '../inc/header.php'; ?>
<?php include '../functions/db.php'; ?>
<?php include '../functions/validate.php'; ?>

<div class="container">
    <h2>Add new member</h2>
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $birthdate = $_POST['birthdate'];

        if (!validate_email($email)) {
            echo "<p style='color:red;'>Email not valid!</p>";
        } elseif (!validate_date($birthdate)) {
            echo "<p style='color:red;'>Birthdate not valid!</p>";
        } else {
            $sql = "INSERT INTO Member (Name, Email, Password, Birthdate) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $password, $birthdate);

            if ($stmt->execute()) {
                echo "<p style='color:green;'>Member added successfully!</p>";
            } else {
                echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
            }
            $stmt->close();
        }
    }
    $conn->close();
    ?>
    <form method="POST">
        <input type="text" name="name" placeholder="Member Name" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <input type="date" name="birthdate" placeholder="Birthdate" required><br>
        <button type="submit">Add</button>
    </form>
</div>

<?php include '../inc/footer.php'; ?>