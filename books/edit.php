<?php
require_once '../security.php';
require_once '../config/database.php';
require_once '../config/book_images.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT * FROM books
     WHERE book_id = ? AND deleted_at IS NULL'
);
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: index.php');
    exit;
}

$error = '';

$authors = $pdo->query(
    "SELECT author_id, CONCAT(first_name, ' ', last_name) AS name
     FROM authors
     ORDER BY last_name, first_name"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $published_year = trim($_POST['published_year'] ?? '');
    $author_id = (int)($_POST['author_id'] ?? 0);
    $last_updated_by = (int)$_SESSION['user_id'];

    $validAuthorIds = array_map(
        'intval',
        array_column($authors, 'author_id')
    );

    if ($title === '' || $isbn === '') {
        $error = 'Enter the book title and ISBN.';
    } elseif (!is_numeric($price) || (float)$price < 0) {
        $error = 'Enter a valid price.';
    } elseif (
        filter_var($published_year, FILTER_VALIDATE_INT) === false ||
        (int)$published_year < 1 ||
        (int)$published_year > 9999
    ) {
        $error = 'Enter a valid publication year.';
    } elseif (!in_array($author_id, $validAuthorIds, true)) {
        $error = 'Select an author.';
    } else {
        $newImagePath = null;

        try {
            $newImagePath = save_book_image($_FILES['image'] ?? []);
            $imagePath = $newImagePath ?? $book['image_path'];

            $stmt = $pdo->prepare(
                'UPDATE books
                 SET title = ?, isbn = ?, price = ?, published_year = ?,
                     author_id = ?, last_updated = NOW(), last_updated_by = ?,
                     image_path = ?
                 WHERE book_id = ? AND deleted_at IS NULL'
            );

            $stmt->execute([
                $title,
                $isbn,
                (float)$price,
                (int)$published_year,
                $author_id,
                $last_updated_by,
                $imagePath,
                $id
            ]);

            if (
                $newImagePath !== null &&
                !empty($book['image_path'])
            ) {
                $oldImage = dirname(__DIR__) . '/' . $book['image_path'];
                if (is_file($oldImage)) {
                    unlink($oldImage);
                }
            }

            header('Location: index.php');
            exit;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        } catch (PDOException $e) {
            $error = 'Could not update the book. The ISBN may already be in use.';

            if ($newImagePath !== null) {
                $newFile = dirname(__DIR__) . '/' . $newImagePath;
                if (is_file($newFile)) {
                    unlink($newFile);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 550px;">
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark fw-bold">Edit Book</div>
        <div class="card-body p-4">
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= escape_xss($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="author_id" class="form-label">Author</label>
                    <select id="author_id" name="author_id" class="form-select" required>
                        <?php foreach ($authors as $author): ?>
                            <option value="<?= (int)$author['author_id'] ?>"
                                <?= (int)$author['author_id'] === (int)$book['author_id']
                                    ? 'selected'
                                    : '' ?>>
                                <?= escape_xss($author['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="title" class="form-label">Book Title</label>
                    <input id="title" type="text" name="title"
                           class="form-control" maxlength="255"
                           value="<?= escape_xss($book['title']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="isbn" class="form-label">ISBN</label>
                    <input id="isbn" type="text" name="isbn"
                           class="form-control" maxlength="20"
                           value="<?= escape_xss($book['isbn']) ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">Price ($)</label>
                        <input id="price" type="number" name="price"
                               class="form-control" min="0" step="0.01"
                               value="<?= escape_xss($book['price']) ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="published_year" class="form-label">
                            Published Year
                        </label>
                        <input id="published_year" type="number"
                               name="published_year" class="form-control"
                               min="1" max="9999"
                               value="<?= escape_xss($book['published_year']) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Book Image</label>

                    <?php if (!empty($book['image_path'])): ?>
                        <div class="mb-2">
                            <img src="../<?= escape_xss($book['image_path']) ?>"
                                 alt="<?= escape_xss($book['title']) ?>"
                                 style="width:100px;height:100px;object-fit:cover;">
                        </div>
                    <?php endif; ?>

                    <input id="image" type="file" name="image"
                           class="form-control"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-text">Leave empty to keep the current image.</div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-warning">Update Book</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
