<?php
session_start();
include 'db.php';

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to home page with message
redirect('index.php', 'You have been successfully logged out.', 'success');
?>
