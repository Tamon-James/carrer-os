<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$uid=require_login();
$repo=new CareerRepository(db());
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
  csrf_verify();
  try{
    $action=post_string('action');
    if($action==='add')$repo->addNote($uid,post_id('company_id')??0,['application_id'=>post_id('application_id'),'category'=>'unset','title'=>post_string('title')?:'メモ','body'=>post_string('body'),'interview_visible'=>isset($_POST['interview_visible']),'tag_ids'=>$_POST['tag_ids']??[]]);
    elseif($action==='update')$repo->updateNote($uid,(int)post_id('note_id'),['title'=>post_string('title'),'body'=>post_string('body'),'interview_visible'=>isset($_POST['interview_visible']),'tag_ids'=>$_POST['tag_ids']??[]]);
    elseif($action==='trash')$repo->trashNote($uid,(int)post_id('note_id'));
    elseif($action==='restore')$repo->restoreNote($uid,(int)post_id('note_id'));
    flash('success','メモを保存しました。');
  }catch(Throwable $e){flash('error',$e->getMessage());}
  redirect('notes.php'.(isset($_POST['trash_view'])?'?trash=1':''));
}
$trash=qp('trash')==='1';
render('notes',['page_title'=>'メモ・カンペ','active_nav'=>'notes','notes'=>$repo->notes($uid,qp('q'),$trash),'tags'=>$repo->noteTags($uid),'companies'=>$repo->companies($uid),'applications'=>$repo->applicationsForUser($uid),'q'=>qp('q'),'trash'=>$trash]);
