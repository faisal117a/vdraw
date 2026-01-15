<?php
// Test script to debug serve_ads.php
$basePath = dirname($_SERVER['PHP_SELF']);
$url = "http://" . $_SERVER['HTTP_HOST'] . $basePath . "/serve_ads.php";
$data = [
    'app_key' => 'tgdraw',
    'placements' => 'tgdraw_top,tgdraw_bottom,tgdraw_sidebar'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Response:\n" . $result;
?>
