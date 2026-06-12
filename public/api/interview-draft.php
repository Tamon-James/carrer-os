<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
$uid=require_login();
if (($_SERVER['REQUEST_METHOD']??'GET')!=='POST') json_response(['error'=>'Method not allowed'],405);
csrf_verify();
$payload=json_decode((string)file_get_contents('php://input'),true);
if (!is_array($payload)) json_response(['error'=>'Invalid JSON'],422);
try {
  $id=(new CareerRepository(db()))->saveDraft($uid,isset($payload['company_id'])?(int)$payload['company_id']:null,isset($payload['application_id'])?(int)$payload['application_id']:null,trim((string)($payload['title']??'面接ログ')),trim((string)($payload['body']??'')));
  json_response(['ok'=>true,'draft_id'=>$id,'saved_at'=>date('H:i:s')]);
} catch(Throwable $e) { json_response(['error'=>$e->getMessage()],422); }

