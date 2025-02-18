<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

require_once 'db_connection.php';



// Pobranie listy użytkowników
$sql = "SELECT id, username, role, created_at FROM users";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Zarządzanie użytkownikami</title>
</head>
<body>
    <h1>Lista użytkowników</h1>
    <a href="add_user.php">Dodaj użytkownika</a>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Nazwa użytkownika</th>
            <th>Rola</th>
            <th>Data utworzenia</th>
            <th>Akcje</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['username'] ?></td>
            <td><?= $row['role'] ?></td>
            <td><?= $row['created_at'] ?></td>
            <td>
                <a href="edit_user.php?id=<?= $row['id'] ?>">Edytuj</a> |
                <a href="delete_user.php?id=<?= $row['id'] ?>" onclick="return confirm('Czy na pewno chcesz usunąć tego użytkownika?')">Usuń</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
