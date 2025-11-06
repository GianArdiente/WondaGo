<?php
session_start();

// Require logged-in user (keeps behavior consistent with rest of app)
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Google Drive file id you provided
$driveId = '1Eia_r5VyUZUqWom9idFgpwZIzD3l0jVR';

// Direct-download URL (Drive uses this pattern; note large files may still require confirmation)
$downloadUrl = "https://drive.google.com/uc?export=download&id={$driveId}";

// Redirect user to the Drive direct-download link
header('Location: ' . $downloadUrl);
exit();