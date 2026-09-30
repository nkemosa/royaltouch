<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
require "../config/db.php";
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$s = $conn->prepare("SELECT * FROM products WHERE id=?");
$s->bind_param("i", $id);
$s->execute();
$p = $s->get_result()->fetch_assoc();
if (!$p) die("Product not found");
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $n = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $d = trim($_POST['description'] ?? '');
    $imageName = $p['image'];
    $newImage = false;
    if ($n === '' || $price <= 0) $error = 'Please enter a product name and a valid price.';
    elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) $error = 'There was a problem uploading the image.';
        elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) $error = 'Image must not be larger than 5MB.';
        else {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
            if (!isset($allowed[$mime])) $error = 'Only JPG, PNG, WEBP or GIF images are allowed.';
            else {
                $uploadDir = dirname(__DIR__) . '/images/products/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $imageName = uniqid('product_', true) . '.' . $allowed[$mime];
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName)) {
                    $error = 'Could not save the uploaded image.';
                    $imageName = $p['image'];
                } else $newImage = true;
            }
        }
    }
    if ($error === '') {
        $s = $conn->prepare("UPDATE products SET name=?,price=?,image=?,description=? WHERE id=?");
        $s->bind_param("sdssi", $n, $price, $imageName, $d, $id);
        if ($s->execute()) {
            if ($newImage && !empty($p['image'])) @unlink(dirname(__DIR__) . '/images/products/' . basename($p['image']));
            header("Location: dashboard.php");
            exit;
        }
        if ($newImage) @unlink(dirname(__DIR__) . '/images/products/' . $imageName);
        $error = 'Could not update the product.';
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../css/style.css">
    <title>Edit Product</title>
</head>

<body>
    <main class="container">
        <div class="card" style="max-width:600px;margin:40px auto">
            <h1>Edit Product</h1>
            <?php if ($error): ?><p style="color:#b00020"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <?php if (!empty($p['image'])): ?><img src="../images/products/<?= htmlspecialchars(basename($p['image'])) ?>" alt="Current product image" style="width:140px;height:140px;object-fit:cover;border-radius:8px"><br><br><?php endif; ?>
            <form method="post" enctype="multipart/form-data"><input type="hidden" name="id" value="<?= $id ?>">
                <label>Name</label><input name="name" value="<?= htmlspecialchars($p['name']) ?>" required>
                <label>Price</label><input type="number" step="0.01" min="0" name="price" value="<?= htmlspecialchars($p['price']) ?>" required>
                <label>Product Image</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"><small>Leave empty to keep the current image. Maximum 5MB.</small>
                <label>Description</label><textarea name="description"><?= htmlspecialchars($p['description']) ?></textarea>
                <button type="submit">Update Product</button> <a class="btn btn-secondary" href="dashboard.php">Cancel</a>
            </form>
        </div>
    </main>
</body>

</html>