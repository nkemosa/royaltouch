<?php
session_start();
require "../config/db.php";

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    $s = $conn->prepare("SELECT id, username, password FROM admins WHERE username=? LIMIT 1");
    $s->bind_param("s", $u);
    $s->execute();
    $a = $s->get_result()->fetch_assoc();

    if ($a && password_verify($p, $a['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $a['id'];
        $_SESSION['admin_username'] = $a['username'];
        header("Location: dashboard.php");
        exit;
    }

    $error = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../css/style.css">
    <title>Admin Login</title>
</head>
<body>
<main class="container">
    <div class="card" style="max-width:450px;margin:70px auto">
        <h1>Admin Login</h1>
        <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="post">
            <label>Username</label>
            <input name="username" autocomplete="username" required>

            <label>Password</label>
            <input type="password" name="password" autocomplete="current-password" required>

            <button type="submit">Login</button>
        </form>

        <p><strong>Default login:</strong> admin / admin123</p>
        <a href="../index.php" class="btn-1">Back to Store</a>
    </div>
</main>
</body>
</html>
