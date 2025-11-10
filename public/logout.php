<?php
// logout.php
require __DIR__ . '/lib/auth.php';

logout_client();
set_flash('success', "Vous avez été déconnecté.");
header('Location: /login.php');
exit;
