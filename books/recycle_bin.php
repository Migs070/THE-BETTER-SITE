<?php
require_once '../security.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
    $restoreId = (int)$_POST['restore_id'];

    $stmt = $pdo->prepare(
        'UPDATE books
         SET deleted_at = NULL, deleted_by = NULL
         WHERE book_id = ? AND deleted_at IS NOT NULL'
    );
    $stmt->execute([$restoreId]);

    header('Location: recycle_bin.php');
    exit;
}

$stmt = $pdo->query(
    "SELECT books.*,
            CONCAT(authors.first_name, ' ', authors.last_name) AS author_name,
            users.username AS deleted_by_username
     FROM books
     JOIN authors ON books.author_id = authors.author_id
     LEFT JOIN users ON books.deleted_by = users.user_id
     WHERE books.deleted_at IS NOT NULL
     ORDER BY books.deleted_at DESC"
);

$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Books Recycle Bin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Books Recycle Bin</h1>
        <a href="index.php" class="btn btn-outline-secondary">Back to Books</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Deleted By</th>
                        <th>Deleted At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($books)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                The recycle bin is empty.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td>#<?= (int)$book['book_id'] ?></td>
                                <td>
                                    <?php if (!empty($book['image_path'])): ?>
                                        <img src="../<?= escape_xss($book['image_path']) ?>"
                                             alt="<?= escape_xss($book['title']) ?>"
                                             style="width:60px;height:60px;object-fit:cover;">
                                    <?php else: ?>
                                        <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= escape_xss($book['title']) ?></td>
                                <td><?= escape_xss($book['author_name']) ?></td>
                                <td>
                                    <?= escape_xss(
                                        $book['deleted_by_username'] ?? 'Unknown user'
                                    ) ?>
                                </td>
                                <td><?= escape_xss($book['deleted_at']) ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="restore_id"
                                               value="<?= (int)$book['book_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
