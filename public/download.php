<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$uid=require_login();
$resource=(new CareerRepository(db()))->resource($uid,(int)qp('id','0'));
if (!$resource||$resource['resource_type']!=='upload') { http_response_code(404); exit('ファイルが見つかりません。'); }
$path=(new ResourceService())->absolutePath($uid,(string)$resource['stored_name']);
if (!is_file($path)) { http_response_code(404); exit('ファイルが見つかりません。'); }
header('Content-Type: '.($resource['mime_type']?:'application/octet-stream'));
header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode((string)$resource['original_name']));
header('Content-Length: '.filesize($path));
readfile($path);

