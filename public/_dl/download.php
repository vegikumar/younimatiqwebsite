<?php
$file = '/var/www/younimatiqwebsite/storage/app/younimatiqwebsite.zip';
if (!is_file($file)) {
    http_response_code(404);
    exit('File not found: '.basename($file));
}
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="younimatiqwebsite.zip"');
header('Content-Length: '.filesize($file));
header('Accept-Ranges: bytes');
ob_end_clean();
readfile($file);
