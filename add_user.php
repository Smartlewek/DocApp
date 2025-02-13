<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $servername = "localhost";
    $username = "php";
    $password = "TK600btk";
    $dbname = "mylogin";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $uname = $_POST['username'];
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    $sql = "INSERT INTO users (username, password, role) VALUES ('$uname', '$pass', '$role')";
    if ($conn->query($sql) === TRUE) {
        header("Location: manage_users.php");
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dodaj użytkownika</title>
</head>
<body>
    <h1>Dodaj nowego użytkownika</h1>
    <form method="post">
        <label>Nazwa użytkownika:</label>
        <input type="text" name="username" required><br>
        <label>Hasło:</label>
        <input type="password" name="password" required><br>
        <label>Rola:</label>
        <select name="role">
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select><br>
        <button type="submit">Dodaj</button>
    </form>
</body>
</html>
