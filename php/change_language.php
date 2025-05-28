<?php
session_start();

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];

    // Optional: Validate supported languages
    $supported = ['en', 'ar', 'fr'];
    if (in_array($lang, $supported)) {
        $_SESSION['lang'] = $lang;
    }
}

header("Location: dashboard.php");
exit();
