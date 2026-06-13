<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$uid=require_login();
$repo=new CareerRepository(db());
$companyId=(int)qp('id','0');
$company=$repo->company($uid,$companyId);
if (!$company) { http_response_code(404); exit('企業が見つかりません。'); }
$tutorialStage=qp('tutorial');
if ($tutorialStage==='1') $tutorialStage='flow';

if (($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
  csrf_verify();
  try {
    $action=post_string('action');
    $tutorialStage=post_string('tutorial_stage');
    if ($action==='add_application') {
      $url=post_string('mypage_url');
      if ($url!==''&&!is_https_url($url)) throw new RuntimeException('マイページURLはHTTPSで入力してください。');
      $repo->addApplication($uid,$companyId,[
        'title'=>post_string('title')?:'応募案件','job_category_id'=>post_id('job_category_id'),
        'interest_level_id'=>post_id('interest_level_id'),'status_id'=>post_id('status_id'),'source_id'=>post_id('source_id'),'agent_id'=>post_id('agent_id'),
        'mypage_url'=>$url?:null,'motivation'=>post_string('motivation')?:null,'next_action'=>post_string('next_action')?:null,
        'deadline_at'=>sql_datetime(post_string('deadline_at')),
      ]);
    } elseif ($action==='add_step') {
      $stepType=post_string('step_type')?:'step';
      $stepTitle=post_string('title');
      if($stepTitle===''&&$stepType==='briefing')$stepTitle='説明会';
      if($stepTitle==='')throw new RuntimeException('説明会以外はステップ名を入力してください。');
      $repo->addStep($uid,(int)(post_id('application_id')??0),[
        'company_id'=>$companyId,
        'title'=>$stepTitle,'step_type'=>$stepType,
        'scheduled_at'=>sql_datetime(post_string('scheduled_at')),'deadline_at'=>sql_datetime(post_string('deadline_at')),
        'url'=>post_string('url')?:null,'memo'=>post_string('memo')?:null,
      ]);
    } elseif ($action==='step_status') {
      $status=post_string('status');
      if (!in_array($status,['todo','doing','done'],true)) throw new RuntimeException('状態が不正です。');
      $repo->updateStepStatus($uid,(int)post_id('step_id'),$status);
    } elseif ($action==='add_note') {
      $repo->addNote($uid,$companyId,['application_id'=>post_id('application_id'),'category'=>post_string('category')?:'company_research','title'=>post_string('title')?:'メモ','body'=>post_string('body'),'interview_visible'=>isset($_POST['interview_visible'])]);
    } elseif ($action==='add_content') {
      $repo->addContent($uid,$companyId,['application_id'=>post_id('application_id'),'category'=>post_string('category')?:'other','title'=>post_string('title')?:'コンテンツ','body'=>post_string('body'),'interview_visible'=>isset($_POST['interview_visible'])?1:0]);
    } elseif ($action==='add_event') {
      $url=post_string('url');
      if ($url!==''&&!is_https_url($url)) throw new RuntimeException('予定URLはHTTPSで入力してください。');
      $startAt=sql_datetime(post_string('start_at'));
      if (!$startAt) throw new RuntimeException('開始日時を入力してください。');
      $repo->addEvent($uid,$companyId,['application_id'=>post_id('application_id'),'event_type'=>post_string('event_type')?:'other','schedule_status'=>post_string('schedule_status')?:'confirmed','title'=>post_string('title')?:'予定','start_at'=>$startAt,'end_at'=>sql_datetime(post_string('end_at')),'location'=>post_string('location')?:null,'url'=>$url?:null,'memo'=>post_string('memo')?:null]);
    } elseif ($action==='add_task') {
      $title=post_string('title');
      if ($title==='') throw new RuntimeException('タスク名を入力してください。');
      $repo->addTask($uid,['company_id'=>$companyId,'application_id'=>post_id('application_id'),'title'=>$title,'due_at'=>sql_datetime(post_string('due_at'))]);
    } elseif ($action==='complete_task') {
      $repo->completeTask($uid,(int)post_id('task_id'));
    } elseif ($action==='archive_company') {
      $repo->setCompanyArchived($uid,$companyId,true);
      flash('success','過去企業へ移動しました。'); redirect('companyFile.php?archived=1');
    } elseif ($action==='restore_company') {
      $repo->setCompanyArchived($uid,$companyId,false);
    } elseif ($action==='archive_application') {
      $repo->setApplicationArchived($uid,(int)post_id('application_id'),true);
    } elseif ($action==='restore_application') {
      $repo->setApplicationArchived($uid,(int)post_id('application_id'),false);
    }
    if ($tutorialStage==='flow'&&$action==='add_step') {
      flash('success','選考フローを追加しました。次はメモを追加します。');
      redirect('company.php?id='.$companyId.'&tutorial=note#notes');
    }
    if ($tutorialStage==='note'&&$action==='add_note') {
      flash('success','メモを追加しました。次は面接モードを開きます。');
      redirect('company.php?id='.$companyId.'&tutorial=interview');
    }
    flash('success','保存しました。');
  } catch(Throwable $e) { flash('error',$e->getMessage()); }
  redirect('company.php?id='.$companyId);
}
render('company',['page_title'=>$company['name'],'active_nav'=>'companies','company'=>$company,'workspace'=>$repo->companyWorkspace($uid,$companyId),'masters'=>$repo->masters($uid),'tutorialStage'=>$tutorialStage]);
