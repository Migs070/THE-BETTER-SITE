<?php
require_once __DIR__ . '/security.php';

if (isset($_SESSION['user_id'])) {
    header('Location: authors/index.php');
} else {
    header('Location: login.php');
}
exit;
