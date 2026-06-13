<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$uid=require_login();
$repo=new CareerRepository(db());
$companyId=ctype_digit(qp('company_id'))?(int)qp('company_id'):null;
$applicationId=ctype_digit(qp('application_id'))?(int)qp('application_id'):null;
$allApplications=$repo->applicationsForUser($uid);
$availability=[];
$availabilitySearched=false;
if ($applicationId) {
  foreach($allApplications as $application) {
    if ((int)$application['application_id']===$applicationId) { $companyId=(int)$application['company_id']; break; }
  }
}
if (($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
  csrf_verify();
  $action=post_string('action');
  try {
    if ($action==='find_availability') {
      $availabilitySearched=true;
      $availability=$repo->findAvailability($uid,['date_from'=>post_string('date_from'),'date_to'=>post_string('date_to'),'duration'=>post_string('duration'),'buffer_before'=>post_string('buffer_before'),'buffer_after'=>post_string('buffer_after'),'interval'=>post_string('interval'),'work_start'=>post_string('work_start'),'work_end'=>post_string('work_end'),'include_weekends'=>isset($_POST['include_weekends'])]);
    } elseif ($action==='add_tentative_event') {
      $startAt=sql_datetime(post_string('start_at'));
      if(!$startAt) throw new RuntimeException('開始日時を入力してください。');
      $repo->addEvent($uid,$companyId,['application_id'=>$applicationId,'event_type'=>'interview','schedule_status'=>'tentative','title'=>post_string('title')?:'面接仮日程','start_at'=>$startAt,'end_at'=>sql_datetime(post_string('end_at')),'location'=>null,'url'=>null,'memo'=>'面接モードの日程調整から登録']);
      flash('success','面接仮日程を登録しました。');
    } elseif ($action==='finalize') {
      $draftId=(int)post_id('draft_id');
      if ($draftId<=0) $draftId=$repo->saveDraft($uid,$companyId,$applicationId,post_string('title')?:'セッションログ',post_string('body'));
      $repo->finalizeDraft($uid,$draftId);
      flash('success','セッションログを保存しました。');
    } elseif ($action==='complete_tutorial') {
      delete_tutorial_data($uid);
      db()->prepare("UPDATE users SET tutorial_status='completed' WHERE user_id=?")->execute([$uid]);
      flash('success','操作チュートリアルが完了しました。チュートリアル企業と関連データを削除しました。');
      redirect('index.php');
    }
  } catch(Throwable $e) { flash('error',$e->getMessage()); }
  if($action!=='find_availability') redirect('campe.php'.($companyId?'?company_id='.$companyId.'&application_id='.(int)$applicationId:''));
}
render('interview',['page_title'=>'セッションモード','active_nav'=>'interview','body_class'=>'interview-mode','interview'=>$repo->interview($uid,$companyId,$applicationId),'companies'=>$repo->companies($uid),'allApplications'=>$allApplications,'companyId'=>$companyId,'applicationId'=>$applicationId,'availability'=>$availability,'availabilitySearched'=>$availabilitySearched,'tutorialStage'=>qp('tutorial')]);
