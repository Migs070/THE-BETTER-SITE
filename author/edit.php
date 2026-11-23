<?php
require_once '../security.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $last_updated_by = (int)$_SESSION['user_id'];

    if ($first_name === '' || $last_name === '') {
        $error = 'Please enter the author’s first and last name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'UPDATE authors
                 SET first_name = ?, last_name = ?, email = ?,
                     last_updated = NOW(), last_updated_by = ?
                 WHERE author_id = ?'
            );
            $stmt->execute([
                $first_name,
                $last_name,
                $email,
                $last_updated_by,
                $id
            ]);

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Could not update the author. The email may already exist.';
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM authors WHERE author_id = ?');
$stmt->execute([$id]);
$author = $stmt->fetch();

if (!$author) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Author</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 500px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-warning text-dark fw-bold">
            Edit Author
        </div>
        <div class="card-body p-4">
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= escape_xss($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input id="first_name"
                           type="text"
                           name="first_name"
                           class="form-control"
                           maxlength="100"
                           value="<?= escape_xss($author['first_name']) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input id="last_name"
                           type="text"
                           name="last_name"
                           class="form-control"
                           maxlength="100"
                           value="<?= escape_xss($author['last_name']) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email"
                           type="email"
                           name="email"
                           class="form-control"
                           maxlength="255"
                           value="<?= escape_xss($author['email']) ?>"
                           required>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-warning">Update Author</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
