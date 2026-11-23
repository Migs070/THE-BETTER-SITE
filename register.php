<?php
require_once 'security.php';
require_once 'config/database.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username     = clean_input($_POST['username'] ?? '');
    $raw_password =$_POST['password'] ?? '';
    $first_name   = clean_input($_POST['first_name'] ?? '');
    $last_name    = clean_input($_POST['last_name'] ?? '');
    $email        = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $age          = (int)($_POST['age'] ?? 0);
    $address      = clean_input($_POST['address'] ?? '');

    // Enforce Minimum 8 Characters + Uppercase + Lowercase + Number
    if (strlen($raw_password) < 8) {$errors[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $raw_password)) {$errors[] = "Password must contain at least one uppercase letter (A-Z).";
    }
    if (!preg_match('/[a-z]/', $raw_password)) {$errors[] = "Password must contain at least one lowercase letter (a-z).";
    }
    if (!preg_match('/[0-9]/', $raw_password)) {$errors[] = "Password must contain at least one number (0-9).";
    }

    if (empty($errors)) {
        $password = password_hash($raw_password, PASSWORD_BCRYPT);

        try {
            $stmt =$pdo->prepare("INSERT INTO users (username, password, first_name, last_name, email, age, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password,$first_name, $last_name,$email, $age,$address]);
            header("Location: login.php?registered=1");
            exit;
        } catch (\PDOException $e) {$errors[] = "Registration failed: Username or Email already exists.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5" style="max-width: 550px;">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white text-center fw-bold">Create New Account</div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as$err): ?>
                                <li><?= escape_xss($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input 
                            type="password" 
                            name="password" 
                            class="form-control" 
                            minlength="8"
                            pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                            title="Must contain at least 8 characters, including uppercase, lowercase letters, and a number."
                            required
                        >
                        <small class="text-muted">Minimum 8 characters with uppercase, lowercase, and numbers.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Register</button>
                </form>
                <div class="text-center mt-3">
                    <small>Already registered? <a href="login.php">Login here</a></small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
