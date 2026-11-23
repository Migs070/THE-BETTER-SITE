<?php
require_once '../security.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$error = '';
$title = '';
$isbn = '';
$price = '';
$published_year = '';
$selected_author_id = (int)($_POST['author_id'] ?? 0);

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
    $added_by = (int)$_SESSION['user_id'];

    $valid_author_ids = array_map(
        'intval',
        array_column($authors, 'author_id')
    );

    if ($title === '' || $isbn === '') {
        $error = 'Please enter the title and ISBN.';
    } elseif (!is_numeric($price) || (float)$price < 0) {
        $error = 'Please enter a valid price.';
    } elseif (
        filter_var($published_year, FILTER_VALIDATE_INT) === false ||
        (int)$published_year < 1 ||
        (int)$published_year > 9999
    ) {
        $error = 'Please enter a valid publication year.';
    } elseif (!in_array($selected_author_id, $valid_author_ids, true)) {
        $error = 'Please select an author.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO books
                    (title, isbn, price, published_year, author_id, added_by)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $title,
                $isbn,
                (float)$price,
                (int)$published_year,
                $selected_author_id,
                $added_by
            ]);

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Could not save the book. The ISBN may already exist.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 500px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white fw-bold">
            Add New Book
        </div>
        <div class="card-body p-4">
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= escape_xss($error) ?></div>
            <?php endif; ?>

            <?php if (empty($authors)): ?>
                <div class="alert alert-warning">
                    Add an author before creating a book.
                    <a href="../author/create.php">Add Author</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="author_id" class="form-label">Author</label>
                        <select id="author_id"
                                name="author_id"
                                class="form-select"
                                required>
                            <option value="">Select an author</option>

                            <?php foreach ($authors as $author): ?>
                                <option value="<?= (int)$author['author_id'] ?>"
                                    <?= $selected_author_id === (int)$author['author_id'] ? 'selected' : '' ?>>
                                    <?= escape_xss($author['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Book Title</label>
                        <input id="title"
                               type="text"
                               name="title"
                               class="form-control"
                               maxlength="255"
                               value="<?= escape_xss($title) ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input id="isbn"
                               type="text"
                               name="isbn"
                               class="form-control"
                               maxlength="20"
                               value="<?= escape_xss($isbn) ?>"
                               required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price ($)</label>
                            <input id="price"
                                   type="number"
                                   name="price"
                                   class="form-control"
                                   min="0"
                                   step="0.01"
                                   value="<?= escape_xss($price) ?>"
                                   required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="published_year" class="form-label">
                                Published Year
                            </label>
                            <input id="published_year"
                                   type="number"
                                   name="published_year"
                                   class="form-control"
                                   min="1"
                                   max="9999"
                                   value="<?= escape_xss($published_year) ?>"
                                   required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success">Save Book</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
