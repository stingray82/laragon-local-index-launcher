<?php
// C:\laragon\www\open-in-sublime.php
// Queues folder paths for a user-session PowerShell watcher to open in Sublime.
// Works even when nginx/php runs as a service (no GUI launch directly from PHP).

$base = realpath('C:\\laragon\\www'); // project root
$dir  = $_GET['dir'] ?? '';

if ($dir === '') {
  http_response_code(400);
  exit('Missing dir');
}

$path = realpath($base . DIRECTORY_SEPARATOR . $dir);

// Security: ensure it resolves inside base AND is a directory
if (!$path || !is_dir($path) || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0) {
  http_response_code(400);
  exit('Invalid path');
}

$queueFile = $base . DIRECTORY_SEPARATOR . '.scripts' . DIRECTORY_SEPARATOR . 'open-sublime-queue.txt';


// Write one path per line (with lock)
file_put_contents($queueFile, $path . PHP_EOL, FILE_APPEND | LOCK_EX);

// Go back to the previous page automatically
$back = $_SERVER['HTTP_REFERER'] ?? '/';
header('Location: ' . $back);
exit;
