<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$uid=require_login();
ensure_user_defaults($uid);
$repo=new CareerRepository(db());
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
  csrf_verify();
  $action=post_string('action');
  if($action==='skip'||$action==='complete'){
    delete_tutorial_data($uid);
    db()->prepare('UPDATE users SET tutorial_status=? WHERE user_id=?')->execute([$action==='skip'?'skipped':'completed',$uid]);
    redirect('index.php');
  }
  if($action==='start'){
    db()->prepare("UPDATE users SET tutorial_status='active' WHERE user_id=?")->execute([$uid]);
    redirect('companyFile.php?tutorial=company#new-company');
  }
}
render('tutorial',['page_title'=>'はじめてのCareer OS','active_nav'=>'']);
