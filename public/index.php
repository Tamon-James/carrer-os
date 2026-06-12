<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(current_user_id()===null) redirect('guide.php');
$uid=require_login();
ensure_user_defaults($uid);
$repo=new CareerRepository(db());
$availability=[];
$availabilitySearched=false;
if (($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
  csrf_verify();
  try {
    $action=post_string('action');
    if ($action==='add_task') {
      $title=post_string('title');
      if ($title==='') throw new RuntimeException('タスク名を入力してください。');
      $repo->addTask($uid,['company_id'=>post_id('company_id'),'application_id'=>post_id('application_id'),'title'=>$title,'due_at'=>sql_datetime(post_string('due_at'))]);
      flash('success','タスクを登録しました。');
    } elseif ($action==='complete_task') {
      $repo->completeTask($uid,(int)post_id('task_id'));
      flash('success','タスクを完了しました。');
    } elseif ($action==='add_event') {
      $startAt=sql_datetime(post_string('start_at'));
      if(!$startAt) throw new RuntimeException('開始日時を入力してください。');
      $url=post_string('url');
      if($url!==''&&!is_https_url($url)) throw new RuntimeException('URLはHTTPSで入力してください。');
      $repo->addEvent($uid,post_id('company_id'),['application_id'=>post_id('application_id'),'event_type'=>post_string('event_type')?:'personal','schedule_status'=>post_string('schedule_status')?:'confirmed','title'=>post_string('title')?:'予定','start_at'=>$startAt,'end_at'=>sql_datetime(post_string('end_at')),'location'=>post_string('location')?:null,'url'=>$url?:null,'memo'=>post_string('memo')?:null]);
      flash('success','予定を登録しました。');
    } elseif ($action==='find_availability') {
      $availabilitySearched=true;
      $availability=$repo->findAvailability($uid,['date_from'=>post_string('date_from'),'date_to'=>post_string('date_to'),'duration'=>post_string('duration'),'buffer_before'=>post_string('buffer_before'),'buffer_after'=>post_string('buffer_after'),'interval'=>post_string('interval'),'work_start'=>post_string('work_start'),'work_end'=>post_string('work_end'),'include_weekends'=>isset($_POST['include_weekends'])]);
    }
  } catch(Throwable $e) { flash('error',$e->getMessage()); }
  if($action!=='find_availability') redirect('index.php');
}
$month=preg_match('/^\d{4}-\d{2}$/',qp('month'))?qp('month'):date('Y-m');
render('dashboard',['page_title'=>'ホーム','active_nav'=>'dashboard','calendar'=>$repo->calendar($uid,$month),'companies'=>$repo->companies($uid),'allApplications'=>$repo->applicationsForUser($uid),'availability'=>$availability,'availabilitySearched'=>$availabilitySearched]+$repo->dashboard($uid));
