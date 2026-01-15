<?php
// Simple IO Proxy for Worker Synchronization
// Handles blocking wait for input and writing input

header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain");

$action = $_GET['action'] ?? '';
$id = preg_replace('/[^a-z0-9]/', '', $_GET['id'] ?? ''); // Sanitize
$val = $_REQUEST['val'] ?? '';

if (!$id) {
    http_response_code(400);
    die("Missing ID");
}

$tempDir = __DIR__ . '/../frontend/tmp_io';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}

$lockFile = "$tempDir/input_$id.txt";

if ($action === 'wait') {
    // Worker waits here
    $waited = 0;
    $timeout = 120; // 2 minutes max wait

    // Clear any previous file (cleanup)
    // Only if we suspect it's stale? No, user might type fast. 
    // Actually, we should probably delete stales before waiting, but hard to know logic.
    // Let's assume unique session IDs generated per RUN handle stales.
    
    while ($waited < $timeout) {
        if (file_exists($lockFile)) {
            $content = file_get_contents($lockFile);
            unlink($lockFile); // Consume
            echo $content;
            exit;
        }
        usleep(500000); // 500ms
        $waited += 0.5;
    }
    echo ""; // Timeout return empty
} elseif ($action === 'write') {
    // Main thread writes here
    file_put_contents($lockFile, $val);
    echo "OK";
}
