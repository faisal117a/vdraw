<?php
if (isset($_POST['content'])) {
    $content = $_POST['content'];
    
    // Force download as attachment
    header('Content-Type: text/x-python; charset=utf-8');
    header('Content-Disposition: attachment; filename="main.py"');
    header('Content-Length: ' . strlen($content));
    header('Connection: close');
    
    // No Cache
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');

    echo $content;
    exit;
}
?>
