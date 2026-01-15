<?php
require_once __DIR__ . '/auth/Auth.php';

// Log out the user
Auth::logout();

// Redirect to login page
header('Location: login.php');
exit;
