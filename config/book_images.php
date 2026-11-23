<?php

function save_book_image(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('The image must be no larger than 5 MB.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new RuntimeException('Please upload a valid image.');
    }

    if (!class_exists('finfo')) {
        throw new RuntimeException('PHP Fileinfo is required to upload images.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Use a JPG, PNG, GIF, or WEBP image.');
    }

    $uploadDirectory = dirname(__DIR__) . '/uploads/books';

    if (
        !is_dir($uploadDirectory) &&
        !mkdir($uploadDirectory, 0755, true) &&
        !is_dir($uploadDirectory)
    ) {
        throw new RuntimeException('Could not create the book image folder.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $destination = $uploadDirectory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }

    return 'uploads/books/' . $filename;
}
