<?php
require_once __DIR__ . '/config.php';

$filename = basename($_GET['f'] ?? '');
if (!$filename || !preg_match('/^[a-f0-9]{32}\.(mp4|webm)$/', $filename)) {
    http_response_code(400); exit('Bad request');
}

$file = UPLOAD_DIR . $filename;
if (!file_exists($file)) { http_response_code(404); exit('Not found'); }

$size  = filesize($file);
$start = 0;
$end   = $size - 1;

header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');

if (isset($_SERVER['HTTP_RANGE'])) {
    $range = $_SERVER['HTTP_RANGE'];
    preg_match('/bytes=(\d+)-(\d*)/', $range, $m);
    $start = (int)$m[1];
    $end   = isset($m[2]) && $m[2] !== '' ? (int)$m[2] : $size - 1;

    if ($start > $end || $end >= $size) {
        http_response_code(416);
        header("Content-Range: bytes */$size");
        exit;
    }

    http_response_code(206);
    header("Content-Range: bytes $start-$end/$size");
} else {
    http_response_code(200);
}

$length = $end - $start + 1;
header("Content-Length: $length");

$fp = fopen($file, 'rb');
fseek($fp, $start);
$remaining = $length;
while (!feof($fp) && $remaining > 0) {
    $chunk = min(8192, $remaining);
    echo fread($fp, $chunk);
    $remaining -= $chunk;
    flush();
}
fclose($fp);
