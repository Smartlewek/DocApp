<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

require_once 'db_connection.php'; // Zaciągnięcie pliku z połączeniem do bazy

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $uname = $_POST['username'];
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $uname, $pass, $role);

    if ($stmt->execute()) {
        header("Location: manage_users.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
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
