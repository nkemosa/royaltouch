<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
require "../config/db.php";

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $n = trim($_POST['name'] ?? '');
    $p = (float)($_POST['price'] ?? 0);
    $d = trim($_POST['description'] ?? '');
    $imageName = null;

    if ($n === '' || $p <= 0) {
        $error = 'Please enter a product name and a valid price.';
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $error = 'There was a problem uploading the image.';
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $error = 'Image must not be larger than 5MB.';
        } else {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
            if (!isset($allowed[$mime])) {
                $error = 'Only JPG, PNG, WEBP or GIF images are allowed.';
            } else {
                $uploadDir = dirname(__DIR__) . '/images/products/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $imageName = uniqid('product_', true) . '.' . $allowed[$mime];
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName)) {
                    $error = 'Could not save the uploaded image.';
                    $imageName = null;
                }
            }
        }
    }

    if ($error === '') {
        $s = $conn->prepare("INSERT INTO products(name,price,image,description) VALUES(?,?,?,?)");
        $s->bind_param("sdss", $n, $p, $imageName, $d);
        if ($s->execute()) {
            header("Location: dashboard.php");
            exit;
        }
        if ($imageName) @unlink(dirname(__DIR__) . '/images/products/' . $imageName);
        $error = 'Could not save the product.';
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../css/style.css">
    <title>Add Product</title>
</head>

<body>
    <main class="container">
        <div class="card" style="max-width:600px;margin:40px auto">
            <h1>Add Product</h1>
            <?php if ($error): ?><p style="color:#b00020"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <label>Name</label><input name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                <label>Price</label><input type="number" step="0.01" min="0" name="price" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                <label>Product Image</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required>
                <small>JPG, PNG, WEBP or GIF — maximum 5MB.</small>
                <label>Description</label><textarea name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                <button type="submit">Save Product</button> <a class="btn btn-secondary" href="dashboard.php">Cancel</a>
            </form>
        </div>
    </main>
</body>

</html>