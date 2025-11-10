<?php
// includes/auth.php
session_start();

function is_client_logged(): bool {
    return isset($_SESSION['client_id']);
}

function require_client_logged(): void {
    if (!is_client_logged()) {
        header("Location: /login.php");
        exit;
    }
}
