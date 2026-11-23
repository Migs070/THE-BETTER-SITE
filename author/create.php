<?php
require_once '../security.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$error = '';
$first_name = '';
$last_name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $added_by = (int)$_SESSION['user_id'];

    if ($first_name === '' || $last_name === '') {
        $error = 'Please enter the author’s first and last name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO authors
                    (first_name, last_name, email, added_by)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$first_name, $last_name, $email, $added_by]);

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Could not save the author. The email may already exist.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Author</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 500px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white fw-bold">
            Add New Author
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
                           value="<?= escape_xss($first_name) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input id="last_name"
                           type="text"
                           name="last_name"
                           class="form-control"
                           maxlength="100"
                           value="<?= escape_xss($last_name) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email"
                           type="email"
                           name="email"
                           class="form-control"
                           maxlength="255"
                           value="<?= escape_xss($email) ?>"
                           required>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success">Save Author</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
