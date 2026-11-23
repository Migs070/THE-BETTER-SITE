<?php
require_once '../security.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM authors WHERE author_id = ?");
    $stmt->execute([(int)$_GET['delete_id']]);
    header("Location: index.php");
    exit;
}

$search = trim($_GET['search'] ?? '');

$sql = "SELECT authors.*, 
               u1.username AS added_by_user, 
               u2.username AS updated_by_user 
        FROM authors 
        LEFT JOIN users u1 ON authors.added_by = u1.user_id 
        LEFT JOIN users u2 ON authors.last_updated_by = u2.user_id";

if ($search !== '') {
    $sql .= " WHERE authors.first_name LIKE ? 
                 OR authors.last_name LIKE ? 
                 OR CONCAT(authors.first_name, ' ', authors.last_name) LIKE ? 
                 OR authors.email LIKE ?";
}

$sql .= " ORDER BY author_id DESC";

$stmt = $pdo->prepare($sql);
if ($search !== '') {
    $param = "%$search%";
    $stmt->execute([$param, $param, $param, $param]);
} else {
    $stmt->execute();
}
$authors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authors List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">📚 Publishing System</a>
            <div class="navbar-nav ms-auto align-items-center">
                <a class="nav-link active me-2" href="index.php">Authors</a>
                <a class="nav-link me-3" href="../books/index.php">Books</a>
                <span class="text-white me-3">User: <strong><?= escape_xss($_SESSION['username']) ?></strong></span>
                <a class="btn btn-sm btn-outline-light" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Authors Directory (Parent Entity)</h2>
            <a href="create.php" class="btn btn-success">+ Add Author</a>
        </div>

        <!-- Search Form -->
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Search by author name or email..." value="<?= escape_xss($search) ?>">
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
                            <th>Name</th>
                            <th>Email</th>
                            <th>Date Added</th>
                            <th>Added By</th>
                            <th>Last Updated By</th>
                            <th>Last Updated At</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($authors)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No author records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($authors as $author): ?>
                            <tr>
                                <td>#<?= escape_xss($author['author_id']) ?></td>
                                <td><?= escape_xss($author['first_name'] . ' ' . $author['last_name']) ?></td>
                                <td><?= escape_xss($author['email']) ?></td>
                                <td><small class="text-muted"><?= escape_xss($author['date_added'] ?? 'N/A') ?></small></td>
                                <td><span class="badge bg-info text-dark"><?= escape_xss($author['added_by_user']) ?></span></td>
                                <td><span class="badge bg-warning text-dark"><?= escape_xss($author['updated_by_user'] ?? 'N/A') ?></span></td>
                                <td><small class="text-muted"><?= escape_xss($author['last_updated'] ?? 'Not yet updated') ?></small></td>
                                <td class="text-center">
                                    <a href="edit.php?id=<?= escape_xss($author['author_id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="index.php?delete_id=<?= escape_xss($author['author_id']) ?>" onclick="return confirm('Delete author and books?')" class="btn btn-sm btn-outline-danger">Delete</a>
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
