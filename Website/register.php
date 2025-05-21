<?php
include 'functions/db.php';
include 'inc/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = $_POST['name'];
    $email     = $_POST['email'];
    $password  = $_POST['password'];
    $birthdate = $_POST['birthdate'];
    $phone     = $_POST['phone'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO Member (Name, Email, Password, Birthdate) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $birthdate);

    if ($stmt->execute()) {

        $member_id = $conn->insert_id;

        $stmt_phone = $conn->prepare("INSERT INTO MemberPhone (MemberID, Phone) VALUES (?, ?)");
        $stmt_phone->bind_param("is", $member_id, $phone);

        if ($stmt_phone->execute()) {
            echo "<p style='color:green;'>Loggedin successfully! <a href='login.php'>Login now</a></p>";
        } else {
            echo "<p style='color:red;'>Error adding phone number: " . $conn->error . "</p>";
        }

        $stmt_phone->close();
    } else {
        echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
    }

    $stmt->close();
    $conn->close();
}
?>

<h2>New Member</h2>
<form method="POST">
    <input type="text" name="name" placeholder="Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <input type="date" name="birthdate" required><br>
    <input type="tel" name="phone" placeholder="Phone" required><br>
    <button type="submit">Register</button>
</form>
<?php include 'inc/footer.php'; ?>