<?php
// Added secure session settings
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle draft saving and publishing logic here
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $type = $_POST['type'] ?? '';
    $action = $_POST['action'] ?? '';

    // Save to database logic (draft or publish)
    // Placeholder for database interaction
    if ($action === 'save') {
        $message = 'Draft saved successfully!';
    } elseif ($action === 'publish') {
        $message = 'Content published successfully!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Author Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="author-container">
        <h1>Welcome, Author</h1>
        <a href="index.html">Go to Main Page</a>
        <form method="POST" action="">
            <label for="type">Content Type:</label>
            <select id="type" name="type" required>
                <option value="blog">Blog</option>
                <option value="short_story">Short Story</option>
                <option value="article">Article</option>
                <option value="novel">Novel</option>
            </select>

            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required>

            <label for="content">Content:</label>
            <textarea id="content" name="content" rows="10" required></textarea>

            <button type="submit" name="action" value="save">Save as Draft</button>
            <button type="submit" name="action" value="publish">Publish</button>
        </form>

        <?php if (isset($message)): ?>
            <p class="message"> <?= htmlspecialchars($message) ?> </p>
        <?php endif; ?>
    </div>
</body>
</html>