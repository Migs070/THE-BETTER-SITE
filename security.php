<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function clean_input(string $value): string
{
    return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

function escape_xss($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
