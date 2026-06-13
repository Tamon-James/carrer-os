<?php
declare(strict_types=1);
require dirname(__DIR__).'/public/bootstrap.php';
if(PHP_SAPI!=='cli')exit("CLI only\n");
$pdo=db();
$uid=(int)$pdo->query('SELECT user_id FROM users ORDER BY user_id LIMIT 1')->fetchColumn();
$companyId=(int)$pdo->query('SELECT company_id FROM companies ORDER BY company_id LIMIT 1')->fetchColumn();
if(!$uid||!$companyId)exit("Seeded user and company required\n");
$repo=new CareerRepository($pdo);
$pdo->beginTransaction();
try{
  $repo->addStep($uid,0,['company_id'=>$companyId,'title'=>'V2同期検証','step_type'=>'interview','scheduled_at'=>'2026-07-01 10:00:00','deadline_at'=>'2026-06-30 18:00:00','url'=>null,'memo'=>null]);
  $stepId=(int)$pdo->query("SELECT step_id FROM flow_steps WHERE title='V2同期検証' ORDER BY step_id DESC LIMIT 1")->fetchColumn();
  $eventCount=(int)$pdo->query("SELECT COUNT(*) FROM events WHERE step_id={$stepId}")->fetchColumn();
  $taskCount=(int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE step_id={$stepId}")->fetchColumn();
  $repo->addNote($uid,0,['application_id'=>null,'category'=>'unset','title'=>'V2メモ検証','body'=>'本文','interview_visible'=>1,'tag_ids'=>[]]);
  $noteId=(int)$pdo->query("SELECT note_id FROM notes WHERE title='V2メモ検証' ORDER BY note_id DESC LIMIT 1")->fetchColumn();
  $repo->updateNote($uid,$noteId,['title'=>'V2メモ検証','body'=>'更新本文','interview_visible'=>1,'tag_ids'=>[]]);
  $versionCount=(int)$pdo->query("SELECT COUNT(*) FROM note_versions WHERE note_id={$noteId}")->fetchColumn();
  $interviewVisibleCount=count($repo->interview($uid,$companyId,null)['contents']);
  $repo->trashNote($uid,$noteId);
  $repo->restoreNote($uid,$noteId);

  $tutorialCompanyId=$repo->addCompany($uid,['name'=>'Tutorial cleanup check','industry_id'=>null,'corporate_url'=>null,'business'=>null,'memo'=>null]);
  $pdo->prepare("INSERT INTO tutorial_records (user_id,entity_type,entity_id) VALUES (?,'company',?)")->execute([$uid,$tutorialCompanyId]);
  $repo->addStep($uid,0,['company_id'=>$tutorialCompanyId,'title'=>'説明会','step_type'=>'briefing','scheduled_at'=>null,'deadline_at'=>null,'url'=>null,'memo'=>null]);
  $repo->addNote($uid,$tutorialCompanyId,['application_id'=>null,'category'=>'briefing','title'=>'説明会メモ','body'=>'本文','interview_visible'=>0,'tag_ids'=>[]]);
  delete_tutorial_data($uid);
  $tutorialCompanyCount=(int)$pdo->query("SELECT COUNT(*) FROM companies WHERE company_id={$tutorialCompanyId}")->fetchColumn();
  $tutorialStepCount=(int)$pdo->query("SELECT COUNT(*) FROM flow_steps WHERE company_id={$tutorialCompanyId}")->fetchColumn();
  $tutorialNoteCount=(int)$pdo->query("SELECT COUNT(*) FROM notes WHERE company_id={$tutorialCompanyId}")->fetchColumn();
  if(!$stepId||$eventCount!==1||$taskCount!==1||$versionCount!==1||$interviewVisibleCount<1||$tutorialCompanyCount!==0||$tutorialStepCount!==0||$tutorialNoteCount!==0)throw new RuntimeException('Operations V2 integration check failed.');
  echo "Operations V2 smoke checks passed.\n";
}finally{
  $pdo->rollBack();
}
