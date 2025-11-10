<?php
require __DIR__ . '/../config/app.php';
session_destroy();
header('Location: /admin/login.php');
exit;
