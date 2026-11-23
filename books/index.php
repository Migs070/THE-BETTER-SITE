<?php
require_once '../security.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    $deletedBy = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare(
        'UPDATE books
         SET deleted_at = NOW(), deleted_by = ?
         WHERE book_id = ? AND deleted_at IS NULL'
    );
    $stmt->execute([$deletedBy, $deleteId]);

    header('Location: index.php');
    exit;
}

$search = trim($_GET['search'] ?? '');

$sql = "SELECT books.*,
               CONCAT(authors.first_name, ' ', authors.last_name) AS author_name,
               u1.username AS added_by_user,
               u2.username AS updated_by_user
        FROM books
        JOIN authors ON books.author_id = authors.author_id
        LEFT JOIN users u1 ON books.added_by = u1.user_id
        LEFT JOIN users u2 ON books.last_updated_by = u2.user_id
        WHERE books.deleted_at IS NULL";

if ($search !== '') {
    $sql .= " AND (
                  books.title LIKE ?
                  OR books.isbn LIKE ?
                  OR CONCAT(authors.first_name, ' ', authors.last_name) LIKE ?
              )";
}

$sql .= ' ORDER BY books.book_id DESC';

$stmt = $pdo->prepare($sql);

if ($search !== '') {
    $param = '%' . $search . '%';
    $stmt->execute([$param, $param, $param]);
} else {
    $stmt->execute();
}

$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Books Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">📚 Publishing System</a>

        <div class="navbar-nav ms-auto align-items-center">
            <a class="nav-link me-2" href="../author/index.php">Authors</a>
            <a class="nav-link active me-3" href="index.php">Books</a>
            <span class="text-white me-3">
                User: <strong><?= escape_xss($_SESSION['username']) ?></strong>
            </span>
            <a class="btn btn-sm btn-outline-light" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Books Catalog</h2>
        <div>
            <a href="recycle_bin.php" class="btn btn-outline-secondary me-2">
                Recycle Bin
            </a>
            <a href="create.php" class="btn btn-success">+ Add Book</a>
        </div>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control"
                   placeholder="Search title, ISBN, or author..."
                   value="<?= escape_xss($search) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search !== ''): ?>
                <a href="index.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Image</th>
                        <th>Author</th>
                        <th>Price</th>
                        <th>Date Added</th>
                        <th>Added By</th>
                        <th>Last Updated By</th>
                        <th>Last Updated At</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($books)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                No book records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td>#<?= (int)$book['book_id'] ?></td>
                                <td class="fw-bold"><?= escape_xss($book['title']) ?></td>
                                <td>
                                    <?php if (!empty($book['image_path'])): ?>
                                        <img src="../<?= escape_xss($book['image_path']) ?>"
                                             alt="<?= escape_xss($book['title']) ?>"
                                             style="width:70px;height:70px;object-fit:cover;">
                                    <?php else: ?>
                                        <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= escape_xss($book['author_name']) ?></td>
                                <td>$<?= number_format((float)$book['price'], 2) ?></td>
                                <td><?= escape_xss($book['date_added'] ?? 'N/A') ?></td>
                                <td><?= escape_xss($book['added_by_user'] ?? 'N/A') ?></td>
                                <td><?= escape_xss($book['updated_by_user'] ?? 'N/A') ?></td>
                                <td><?= escape_xss($book['last_updated'] ?? 'Not yet updated') ?></td>
                                <td class="text-center">
                                    <a href="edit.php?id=<?= (int)$book['book_id'] ?>"
                                       class="btn btn-sm btn-outline-primary">Edit</a>

                                    <form method="POST" class="d-inline"
                                          onsubmit="return confirm('Move this book to the recycle bin?');">
                                        <input type="hidden" name="delete_id"
                                               value="<?= (int)$book['book_id'] ?>">
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger">
                                            Delete
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
