<?php
// File di debug temporaneo per testare upload avatar
session_start();
require_once __DIR__ . '/../includes/helpers.php';

echo "<h1>Debug Upload Avatar</h1>";

// Test 1: Directory exists?
$upload_dir = __DIR__ . '/../data/avatars/';
echo "<h2>Test 1: Directory Check</h2>";
echo "Upload dir path: <code>" . $upload_dir . "</code><br>";
echo "Real path: <code>" . realpath($upload_dir) . "</code><br>";
echo "Directory exists: " . (is_dir($upload_dir) ? "✅ YES" : "❌ NO") . "<br>";
echo "Is writable: " . (is_writable($upload_dir) ? "✅ YES" : "❌ NO") . "<br>";

// Test 2: Permissions
echo "<h2>Test 2: Permissions</h2>";
if (is_dir($upload_dir)) {
    $perms = fileperms($upload_dir);
    echo "Permissions: " . substr(sprintf('%o', $perms), -4) . "<br>";
}

// Test 3: File upload test
echo "<h2>Test 3: Upload Test</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_avatar'])) {
    echo "<pre>";
    print_r($_FILES['test_avatar']);
    echo "</pre>";

    $file = $_FILES['test_avatar'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $test_filename = 'test_' . time() . '.jpg';
        $target = $upload_dir . $test_filename;
        echo "Target path: <code>$target</code><br>";

        if (move_uploaded_file($file['tmp_name'], $target)) {
            echo "✅ Upload SUCCESS!<br>";
            echo "File saved at: <code>$target</code><br>";
            echo "URL path: <code>/data/avatars/$test_filename</code><br>";
            echo "<img src='/data/avatars/$test_filename' style='max-width:200px'><br>";
        } else {
            echo "❌ Upload FAILED!<br>";
            echo "Error: " . error_get_last()['message'] . "<br>";
        }
    } else {
        echo "❌ Upload error code: " . $file['error'] . "<br>";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <h2>Test Upload</h2>
    <input type="file" name="test_avatar" accept="image/*">
    <button type="submit">Test Upload</button>
</form>

<hr>
<a href="/forms/settings.php">← Back to Settings</a>