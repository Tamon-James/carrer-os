<?php
declare(strict_types=1);

final class CareerRepository {
  public function __construct(private PDO $pdo) {}

  public function dashboard(int $uid): array {
    $today = date('Y-m-d');
    $events = $this->all(
      "SELECT e.*, c.name company_name, a.title application_title
       FROM events e LEFT JOIN companies c ON c.company_id=e.company_id
       LEFT JOIN applications a ON a.application_id=e.application_id
       WHERE e.user_id=? AND e.state='active' AND DATE(e.start_at)=?
       ORDER BY e.start_at LIMIT 12", [$uid, $today]
    );
    $deadlines = $this->all(
      "SELECT * FROM (
        SELECT a.application_id item_id,a.title,a.deadline_at,c.company_id,c.name company_name,'応募' item_type FROM applications a JOIN companies c ON c.company_id=a.company_id WHERE a.user_id=? AND a.rejected=0 AND a.deadline_at IS NOT NULL
        UNION ALL SELECT f.step_id,f.title,f.deadline_at,c.company_id,c.name,'選考' FROM flow_steps f JOIN companies c ON c.company_id=f.company_id WHERE f.user_id=? AND f.status<>'done' AND f.deadline_at IS NOT NULL
        UNION ALL SELECT t.task_id,t.title,t.due_at,c.company_id,c.name,'タスク' FROM tasks t LEFT JOIN companies c ON c.company_id=t.company_id WHERE t.user_id=? AND t.done=0 AND t.hidden=0 AND t.due_at IS NOT NULL
       ) deadlines ORDER BY deadline_at LIMIT 8", [$uid,$uid,$uid]
    );
    $nextSteps = $this->all(
      "SELECT f.*,a.title application_title,c.company_id,c.name company_name
       FROM flow_steps f LEFT JOIN applications a ON a.application_id=f.application_id
       JOIN companies c ON c.company_id=f.company_id
       WHERE f.user_id=? AND f.status<>'done'
       ORDER BY COALESCE(f.scheduled_at,f.deadline_at,'9999-12-31'),f.sort_order LIMIT 8", [$uid]
    );
    $recentNotes = $this->all(
      "SELECT n.*,c.name company_name FROM notes n LEFT JOIN companies c ON c.company_id=n.company_id
       WHERE n.user_id=? ORDER BY n.updated_at DESC LIMIT 6", [$uid]
    );
    $tasks = $this->all(
      "SELECT t.*,c.name company_name,a.title application_title
       FROM tasks t LEFT JOIN companies c ON c.company_id=t.company_id
       LEFT JOIN applications a ON a.application_id=t.application_id
       WHERE t.user_id=? AND t.done=0 AND t.hidden=0
       ORDER BY CASE WHEN t.due_at IS NULL THEN 1 ELSE 0 END,t.due_at,t.task_id LIMIT 20",[$uid]
    );
    return compact('events', 'deadlines', 'nextSteps', 'recentNotes','tasks');
  }

  public function calendar(int $uid,string $month): array {
    $start=DateTimeImmutable::createFromFormat('!Y-m-d',$month.'-01') ?: new DateTimeImmutable('first day of this month');
    $gridStart=$start->modify('monday this week');
    $gridEnd=$start->modify('last day of this month')->modify('sunday this week')->modify('+1 day');
    $events=$this->all("SELECT e.*,c.name company_name FROM events e LEFT JOIN companies c ON c.company_id=e.company_id WHERE e.user_id=? AND e.state='active' AND e.start_at>=? AND e.start_at<? ORDER BY e.start_at",[$uid,$gridStart->format('Y-m-d H:i:s'),$gridEnd->format('Y-m-d H:i:s')]);
    $byDate=[];
    foreach($events as $event) $byDate[substr($event['start_at'],0,10)][]=$event;
    return ['month'=>$start->format('Y-m'),'start'=>$gridStart,'end'=>$gridEnd,'eventsByDate'=>$byDate,'events'=>$events];
  }

  public function dayEvents(int $uid,string $date): array {
    return $this->all(
      "SELECT e.*,c.name company_name,a.title application_title
       FROM events e LEFT JOIN companies c ON c.company_id=e.company_id
       LEFT JOIN applications a ON a.application_id=e.application_id
       WHERE e.user_id=? AND e.state='active' AND e.start_at>=? AND e.start_at<?
       ORDER BY e.start_at,e.event_id",
      [$uid,$date.' 00:00:00',(new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d 00:00:00')]
    );
  }

  public function companies(int $uid, string $query = '', bool $archived = false): array {
    $sql = "SELECT c.*,i.name industry_name,
      (SELECT COUNT(*) FROM applications ac WHERE ac.company_id=c.company_id) application_count,
      (SELECT MIN(ad.deadline_at) FROM applications ad WHERE ad.company_id=c.company_id AND ad.rejected=0) next_deadline
      FROM companies c LEFT JOIN industries i ON i.industry_id=c.industry_id
      WHERE c.user_id=? AND c.archived=?";
    $params = [$uid,$archived?1:0];
    if ($query !== '') {
      $sql .= ' AND (c.name LIKE ? OR c.business LIKE ?)';
      $params[] = "%{$query}%"; $params[] = "%{$query}%";
    }
    $sql .= ' ORDER BY c.updated_at DESC';
    return $this->all($sql, $params);
  }

  public function company(int $uid, int $companyId): ?array {
    return $this->one("SELECT c.*,i.name industry_name FROM companies c LEFT JOIN industries i ON i.industry_id=c.industry_id WHERE c.company_id=? AND c.user_id=?", [$companyId,$uid]);
  }

  public function companyWorkspace(int $uid, int $companyId): array {
    $applications = $this->all(
      "SELECT a.*,s.name status_name,j.name job_name,l.name interest_name,ag.name agent_name
       FROM applications a LEFT JOIN statuses s ON s.status_id=a.status_id
       LEFT JOIN job_categories j ON j.job_category_id=a.job_category_id
       LEFT JOIN interest_levels l ON l.interest_level_id=a.interest_level_id
       LEFT JOIN agents ag ON ag.agent_id=a.agent_id
       WHERE a.user_id=? AND a.company_id=? ORDER BY a.archived,a.rejected,a.updated_at DESC", [$uid,$companyId]
    );
    $steps = $this->all(
      "SELECT f.*,a.title application_title FROM flow_steps f LEFT JOIN applications a ON a.application_id=f.application_id
       WHERE f.user_id=? AND f.company_id=? ORDER BY f.application_id,f.sort_order,f.step_id", [$uid,$companyId]
    );
    $contents = $this->all("SELECT * FROM contents WHERE user_id=? AND (company_id=? OR company_id IS NULL) ORDER BY category,sort_order,updated_at DESC", [$uid,$companyId]);
    $notes = $this->all("SELECT * FROM notes WHERE user_id=? AND company_id=? ORDER BY updated_at DESC LIMIT 20", [$uid,$companyId]);
    $logs = $this->all("SELECT * FROM interview_logs WHERE user_id=? AND company_id=? ORDER BY occurred_at DESC LIMIT 20", [$uid,$companyId]);
    $resources = $this->all("SELECT * FROM resources WHERE user_id=? AND company_id=? ORDER BY updated_at DESC", [$uid,$companyId]);
    $events = $this->all("SELECT * FROM events WHERE user_id=? AND company_id=? AND state='active' ORDER BY start_at LIMIT 10", [$uid,$companyId]);
    $tasks = $this->all("SELECT t.*,a.title application_title FROM tasks t LEFT JOIN applications a ON a.application_id=t.application_id WHERE t.user_id=? AND t.company_id=? AND t.done=0 AND t.hidden=0 ORDER BY CASE WHEN t.due_at IS NULL THEN 1 ELSE 0 END,t.due_at",[$uid,$companyId]);
    return compact('applications','steps','contents','notes','logs','resources','events','tasks');
  }

  public function masters(int $uid): array {
    return [
      'industries'=>$this->all('SELECT * FROM industries WHERE user_id=? ORDER BY sort_order',[$uid]),
      'jobs'=>$this->all('SELECT * FROM job_categories WHERE user_id=? ORDER BY sort_order',[$uid]),
      'interests'=>$this->all('SELECT * FROM interest_levels WHERE user_id=? ORDER BY sort_order',[$uid]),
      'statuses'=>$this->all('SELECT * FROM statuses WHERE user_id=? ORDER BY sort_order',[$uid]),
      'sources'=>$this->all('SELECT * FROM application_sources WHERE user_id=? ORDER BY sort_order',[$uid]),
      'agents'=>$this->all('SELECT * FROM agents WHERE user_id=? ORDER BY name',[$uid]),
    ];
  }

  public function addCompany(int $uid, array $input): int {
    $input['industry_id']=$this->ownedId('industries','industry_id',$input['industry_id'],$uid);
    $stmt=$this->pdo->prepare('INSERT INTO companies (user_id,name,industry_id,corporate_url,business,memo) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$uid,$input['name'],$input['industry_id'],$input['corporate_url'],$input['business'],$input['memo']]);
    return (int)$this->pdo->lastInsertId();
  }

  public function addMaster(int $uid,string $type,string $name): void {
    $map=['industry'=>'industries','job'=>'job_categories','interest'=>'interest_levels','status'=>'statuses','source'=>'application_sources'];
    if (!isset($map[$type])||$name==='') throw new RuntimeException('分類の指定が不正です。');
    $table=$map[$type];
    $stmt=$this->pdo->prepare("INSERT INTO {$table} (user_id,name,sort_order) SELECT ?,?,COALESCE(MAX(sort_order)+10,0) FROM {$table} WHERE user_id=?");
    $stmt->execute([$uid,$name,$uid]);
  }

  public function addAgent(int $uid,string $name): void {
    if ($name==='') throw new RuntimeException('エージェント名を入力してください。');
    $this->pdo->prepare('INSERT INTO agents (user_id,name) VALUES (?,?)')->execute([$uid,$name]);
  }

  public function addApplication(int $uid, int $companyId, array $input): int {
    if (!$this->company($uid,$companyId)) throw new RuntimeException('企業が見つかりません。');
    $input['job_category_id']=$this->ownedId('job_categories','job_category_id',$input['job_category_id'],$uid);
    $input['interest_level_id']=$this->ownedId('interest_levels','interest_level_id',$input['interest_level_id'],$uid);
    $input['status_id']=$this->ownedId('statuses','status_id',$input['status_id'],$uid);
    $input['source_id']=$this->ownedId('application_sources','source_id',$input['source_id'],$uid);
    $input['agent_id']=$this->ownedId('agents','agent_id',$input['agent_id'],$uid);
    $stmt=$this->pdo->prepare('INSERT INTO applications (user_id,company_id,title,job_category_id,interest_level_id,status_id,source_id,agent_id,mypage_url,motivation,next_action,deadline_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$uid,$companyId,$input['title'],$input['job_category_id'],$input['interest_level_id'],$input['status_id'],$input['source_id'],$input['agent_id'],$input['mypage_url'],$input['motivation'],$input['next_action'],$input['deadline_at']]);
    return (int)$this->pdo->lastInsertId();
  }

  public function addStep(int $uid, int $applicationId, array $input): void {
    $applicationId=$applicationId>0?$applicationId:null;
    $companyId=(int)($input['company_id']??0);
    if($applicationId){
      $owned=$this->one('SELECT company_id FROM applications WHERE application_id=? AND user_id=?',[$applicationId,$uid]);
      if(!$owned)throw new RuntimeException('応募案件が見つかりません。');
      $companyId=(int)$owned['company_id'];
    }
    if(!$this->company($uid,$companyId))throw new RuntimeException('企業が見つかりません。');
    $stmt=$this->pdo->prepare("INSERT INTO flow_steps (user_id,company_id,application_id,title,step_type,status,sort_order,scheduled_at,deadline_at,url,memo) SELECT ?,?,?,?,?,'todo',COALESCE(MAX(sort_order)+10,0),?,?,?,? FROM flow_steps WHERE company_id=? AND application_id<=>?");
    $stmt->execute([$uid,$companyId,$applicationId,$input['title'],$input['step_type'],$input['scheduled_at'],$input['deadline_at'],$input['url'],$input['memo'],$companyId,$applicationId]);
    $stepId=(int)$this->pdo->lastInsertId();
    if($input['scheduled_at'])$this->pdo->prepare("INSERT INTO events (user_id,company_id,application_id,step_id,event_type,schedule_status,title,start_at,end_at) VALUES (?,?,?,?,?,'confirmed',?,?,DATE_ADD(?,INTERVAL 60 MINUTE))")->execute([$uid,$companyId,$applicationId,$stepId,$input['step_type'],$input['title'],$input['scheduled_at'],$input['scheduled_at']]);
    if($input['deadline_at'])$this->pdo->prepare("INSERT INTO tasks (user_id,company_id,application_id,step_id,title,due_at) VALUES (?,?,?,?,?,?)")->execute([$uid,$companyId,$applicationId,$stepId,$input['title'].' 締切',$input['deadline_at']]);
  }

  public function updateStepStatus(int $uid, int $stepId, string $status): void {
    $stmt=$this->pdo->prepare('UPDATE flow_steps SET status=? WHERE step_id=? AND user_id=?');
    $stmt->execute([$status,$stepId,$uid]);
    if($status==='done')$this->pdo->prepare('UPDATE tasks SET done=1 WHERE step_id=? AND user_id=?')->execute([$stepId,$uid]);
  }

  public function addNote(int $uid, int $companyId, array $input): void {
    $companyId=$companyId>0?$companyId:null;
    if(!empty($input['application_id'])){
      $owned=$this->one('SELECT company_id FROM applications WHERE application_id=? AND user_id=?',[(int)$input['application_id'],$uid]);
      if(!$owned)throw new RuntimeException('応募案件が見つかりません。');
      $companyId=(int)$owned['company_id'];
    }
    if($companyId&&!$this->company($uid,$companyId))throw new RuntimeException('企業が見つかりません。');
    $input['application_id']=$companyId?$this->ownedApplication($uid,$companyId,$input['application_id']):null;
    $scope=$companyId?'linked':'common';
    $stmt=$this->pdo->prepare('INSERT INTO notes (user_id,company_id,application_id,category,title,body,interview_visible,note_scope) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$uid,$companyId,$input['application_id'],$input['category']?:'unset',$input['title'],$input['body'],!empty($input['interview_visible'])?1:0,$scope]);
    $this->syncNoteTags($uid,(int)$this->pdo->lastInsertId(),$input['tag_ids']??[]);
  }

  public function addContent(int $uid, int $companyId, array $input): void {
    $input['application_id']=$this->ownedApplication($uid,$companyId,$input['application_id']);
    $stmt=$this->pdo->prepare('INSERT INTO contents (user_id,company_id,application_id,category,title,body,interview_visible) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$uid,$companyId,$input['application_id'],$input['category'],$input['title'],$input['body'],$input['interview_visible']]);
  }

  public function addEvent(int $uid, ?int $companyId, array $input): void {
    if ($companyId && !$this->company($uid,$companyId)) throw new RuntimeException('企業が見つかりません。');
    if ($input['application_id']) {
      $owned=$this->one('SELECT company_id FROM applications WHERE application_id=? AND user_id=?',[$input['application_id'],$uid]);
      if (!$owned) throw new RuntimeException('応募案件が見つかりません。');
      $companyId=(int)$owned['company_id'];
    }
    $scheduleStatus=in_array($input['schedule_status']??'confirmed',['confirmed','tentative'],true)?$input['schedule_status']:'confirmed';
    try {
      $startAt=new DateTimeImmutable($input['start_at']);
      $endAt=$input['end_at'] ? new DateTimeImmutable($input['end_at']) : $startAt->modify('+60 minutes');
    } catch (Exception) {
      throw new RuntimeException('日時を正しく入力してください。');
    }
    if ($endAt<=$startAt) throw new RuntimeException('終了日時は開始日時より後にしてください。');
    $stmt=$this->pdo->prepare('INSERT INTO events (user_id,company_id,application_id,event_type,schedule_status,title,start_at,end_at,location,url,memo) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$uid,$companyId,$input['application_id'],$input['event_type'],$scheduleStatus,$input['title'],$startAt->format('Y-m-d H:i:s'),$endAt->format('Y-m-d H:i:s'),$input['location'],$input['url'],$input['memo']]);
  }

  public function findAvailability(int $uid,array $input): array {
    $from=new DateTimeImmutable($input['date_from'].' 00:00:00');
    $to=new DateTimeImmutable($input['date_to'].' 23:59:59');
    if ($to<$from || $to->diff($from)->days>31) throw new RuntimeException('検索期間は31日以内で指定してください。');
    $duration=max(15,min(480,(int)$input['duration']));
    $before=max(0,min(180,(int)$input['buffer_before']));
    $after=max(0,min(180,(int)$input['buffer_after']));
    $interval=max(15,min(120,(int)$input['interval']));
    $workStart=$input['work_start'];
    $workEnd=$input['work_end'];
    if (!preg_match('/^\d{2}:\d{2}$/',$workStart) || !preg_match('/^\d{2}:\d{2}$/',$workEnd) || $workStart >= $workEnd) throw new RuntimeException('検索時間帯が不正です。');
    $events=$this->all("SELECT start_at,COALESCE(end_at,DATE_ADD(start_at,INTERVAL 60 MINUTE)) end_at FROM events WHERE user_id=? AND state='active' AND start_at<? AND COALESCE(end_at,DATE_ADD(start_at,INTERVAL 60 MINUTE))>?",[$uid,$to->format('Y-m-d H:i:s'),$from->format('Y-m-d H:i:s')]);
    $slots=[];
    for($day=$from;$day<=$to;$day=$day->modify('+1 day')){
      if (in_array((int)$day->format('N'),[6,7],true) && empty($input['include_weekends'])) continue;
      $windowStart=new DateTimeImmutable($day->format('Y-m-d').' '.$workStart.':00');
      $windowEnd=new DateTimeImmutable($day->format('Y-m-d').' '.$workEnd.':00');
      for($candidate=$windowStart;$candidate->modify("+{$duration} minutes")<=$windowEnd;$candidate=$candidate->modify("+{$interval} minutes")){
        $blockStart=$candidate->modify("-{$before} minutes");
        $end=$candidate->modify("+{$duration} minutes");
        $blockEnd=$end->modify("+{$after} minutes");
        $free=true;
        foreach($events as $event){
          $eventStart=new DateTimeImmutable($event['start_at']);
          $eventEnd=new DateTimeImmutable($event['end_at']);
          if($blockStart<$eventEnd && $blockEnd>$eventStart){$free=false;break;}
        }
        if($free)$slots[]=['start_at'=>$candidate->format('Y-m-d H:i:s'),'end_at'=>$end->format('Y-m-d H:i:s'),'label'=>$candidate->format('m/d').'（'.['','月','火','水','木','金','土','日'][(int)$candidate->format('N')].'）'.$candidate->format('H:i').' - '.$end->format('H:i')];
        if(count($slots)>=100) return $slots;
      }
    }
    return $slots;
  }

  public function addTask(int $uid,array $input): void {
    $companyId=$input['company_id'];
    $applicationId=$input['application_id'];
    if ($companyId && !$this->company($uid,$companyId)) throw new RuntimeException('企業が見つかりません。');
    if ($applicationId) {
      $owned=$this->one('SELECT company_id FROM applications WHERE application_id=? AND user_id=?',[$applicationId,$uid]);
      if (!$owned) throw new RuntimeException('応募案件が見つかりません。');
      $companyId=(int)$owned['company_id'];
    }
    $stmt=$this->pdo->prepare('INSERT INTO tasks (user_id,company_id,application_id,title,due_at) VALUES (?,?,?,?,?)');
    $stmt->execute([$uid,$companyId,$applicationId,$input['title'],$input['due_at']]);
  }

  public function completeTask(int $uid,int $taskId): void {
    $stmt=$this->pdo->prepare('UPDATE tasks SET done=1 WHERE task_id=? AND user_id=?');
    $stmt->execute([$taskId,$uid]);
    if ($stmt->rowCount()===0) throw new RuntimeException('タスクが見つかりません。');
  }

  public function applicationsForUser(int $uid): array {
    return $this->all('SELECT a.application_id,a.title,c.company_id,c.name company_name FROM applications a JOIN companies c ON c.company_id=a.company_id WHERE a.user_id=? AND a.rejected=0 ORDER BY c.name,a.title',[$uid]);
  }

  public function addResource(int $uid, int $companyId, array $input): int {
    if (!$this->company($uid,$companyId)) throw new RuntimeException('企業が見つかりません。');
    $input['application_id']=$this->ownedApplication($uid,$companyId,$input['application_id']);
    $stmt=$this->pdo->prepare('INSERT INTO resources (user_id,company_id,application_id,resource_type,category,display_name,original_name,stored_name,mime_type,size_bytes,external_url) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$uid,$companyId,$input['application_id'],$input['resource_type'],$input['category'],$input['display_name'],$input['original_name'],$input['stored_name'],$input['mime_type'],$input['size_bytes'],$input['external_url']]);
    return (int)$this->pdo->lastInsertId();
  }

  public function resource(int $uid, int $resourceId): ?array {
    return $this->one('SELECT * FROM resources WHERE resource_id=? AND user_id=?',[$resourceId,$uid]);
  }

  public function interview(int $uid, ?int $companyId, ?int $applicationId): array {
    $company=$companyId ? $this->company($uid,$companyId) : null;
    $contents=$this->all("SELECT note_id content_id,category,title,body,application_id,company_id FROM notes WHERE user_id=? AND deleted_at IS NULL AND interview_visible=1 AND (company_id IS NULL OR company_id=?) AND (? IS NULL OR application_id IS NULL OR application_id=?) ORDER BY updated_at DESC",[$uid,$companyId,$applicationId,$applicationId]);
    $scope=$applicationId ? 'application:'.$applicationId : ($companyId ? 'company:'.$companyId : 'global');
    $draft=$this->one('SELECT * FROM interview_drafts WHERE user_id=? AND draft_scope=?',[$uid,$scope]);
    return compact('company','contents','draft');
  }

  public function notes(int $uid,string $query='',bool $trash=false): array {
    $sql="SELECT n.*,c.name company_name,a.title application_title,
      GROUP_CONCAT(DISTINCT t.name ORDER BY t.sort_order SEPARATOR ', ') tag_names
      FROM notes n LEFT JOIN companies c ON c.company_id=n.company_id
      LEFT JOIN applications a ON a.application_id=n.application_id
      LEFT JOIN note_tag_links l ON l.note_id=n.note_id LEFT JOIN note_tags t ON t.tag_id=l.tag_id
      WHERE n.user_id=? AND n.deleted_at IS ".($trash?'NOT NULL':'NULL');
    $params=[$uid];
    if($query!==''){$sql.=" AND (n.title LIKE ? OR n.body LIKE ? OR c.name LIKE ? OR a.title LIKE ? OR t.name LIKE ?)";for($i=0;$i<5;$i++)$params[]="%{$query}%";}
    $sql.=" GROUP BY n.note_id ORDER BY n.updated_at DESC";
    return $this->all($sql,$params);
  }

  public function noteTags(int $uid): array { return $this->all('SELECT * FROM note_tags WHERE user_id=? ORDER BY sort_order,name',[$uid]); }

  public function updateNote(int $uid,int $noteId,array $input): void {
    $note=$this->one('SELECT * FROM notes WHERE note_id=? AND user_id=?',[$noteId,$uid]);
    if(!$note)throw new RuntimeException('メモが見つかりません。');
    $ownsTransaction=!$this->pdo->inTransaction();
    if($ownsTransaction)$this->pdo->beginTransaction();
    try{
      $this->pdo->prepare('INSERT INTO note_versions (user_id,note_id,title,body,reason) VALUES (?,?,?,?,?)')->execute([$uid,$noteId,$note['title'],$note['body'],'save']);
      $this->pdo->prepare('UPDATE notes SET title=?,body=?,interview_visible=?,deleted_at=NULL WHERE note_id=? AND user_id=?')->execute([$input['title'],$input['body'],!empty($input['interview_visible'])?1:0,$noteId,$uid]);
      $this->syncNoteTags($uid,$noteId,$input['tag_ids']??[]);
      $this->pdo->prepare("DELETE FROM note_versions WHERE note_id=? AND version_id NOT IN (SELECT version_id FROM (SELECT version_id FROM note_versions WHERE note_id=? ORDER BY created_at DESC,version_id DESC LIMIT 50) recent)")->execute([$noteId,$noteId]);
      if($ownsTransaction)$this->pdo->commit();
    }catch(Throwable $e){if($ownsTransaction&&$this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
  }

  public function trashNote(int $uid,int $noteId): void { $this->pdo->prepare('UPDATE notes SET deleted_at=NOW() WHERE note_id=? AND user_id=?')->execute([$noteId,$uid]); }
  public function restoreNote(int $uid,int $noteId): void { $this->pdo->prepare('UPDATE notes SET deleted_at=NULL WHERE note_id=? AND user_id=?')->execute([$noteId,$uid]); }

  public function setCompanyArchived(int $uid,int $companyId,bool $archived): void {
    $this->pdo->prepare('UPDATE companies SET archived=? WHERE company_id=? AND user_id=?')->execute([$archived?1:0,$companyId,$uid]);
    $this->recordState($uid,'company',$companyId,$archived?'archive':'restore',$archived?'過去企業へ移動':'通常企業へ復元');
  }

  public function setApplicationArchived(int $uid,int $applicationId,bool $archived): void {
    $this->pdo->prepare('UPDATE applications SET archived=? WHERE application_id=? AND user_id=?')->execute([$archived?1:0,$applicationId,$uid]);
    $this->recordState($uid,'application',$applicationId,$archived?'archive':'restore',$archived?'応募案件をアーカイブ':'応募案件を復元');
  }

  public function saveDraft(int $uid, ?int $companyId, ?int $applicationId, string $title, string $body): int {
    if ($companyId && !$this->company($uid,$companyId)) throw new RuntimeException('企業が見つかりません。');
    if ($applicationId) {
      $owned=$this->one('SELECT company_id FROM applications WHERE application_id=? AND user_id=?',[$applicationId,$uid]);
      if (!$owned) throw new RuntimeException('応募案件が見つかりません。');
      $companyId=(int)$owned['company_id'];
    }
    $scope=$applicationId ? 'application:'.$applicationId : ($companyId ? 'company:'.$companyId : 'global');
    $stmt=$this->pdo->prepare("INSERT INTO interview_drafts (user_id,company_id,application_id,draft_scope,title,body) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE company_id=VALUES(company_id),application_id=VALUES(application_id),title=VALUES(title),body=VALUES(body)");
    $stmt->execute([$uid,$companyId,$applicationId,$scope,$title,$body]);
    $row=$this->one('SELECT draft_id FROM interview_drafts WHERE user_id=? AND draft_scope=?',[$uid,$scope]);
    return (int)($row['draft_id']??0);
  }

  public function finalizeDraft(int $uid, int $draftId): void {
    $draft=$this->one('SELECT * FROM interview_drafts WHERE draft_id=? AND user_id=?',[$draftId,$uid]);
    if (!$draft) throw new RuntimeException('下書きが見つかりません。');
    $this->pdo->beginTransaction();
    try {
      $stmt=$this->pdo->prepare('INSERT INTO interview_logs (user_id,company_id,application_id,title,body) VALUES (?,?,?,?,?)');
      $stmt->execute([$uid,$draft['company_id'],$draft['application_id'],$draft['title'],$draft['body']]);
      $this->pdo->prepare('DELETE FROM interview_drafts WHERE draft_id=? AND user_id=?')->execute([$draftId,$uid]);
      $this->pdo->commit();
    } catch(Throwable $e) {
      $this->pdo->rollBack(); throw $e;
    }
  }

  private function all(string $sql,array $params=[]): array {
    $stmt=$this->pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
  }
  private function one(string $sql,array $params=[]): ?array {
    $stmt=$this->pdo->prepare($sql); $stmt->execute($params); $row=$stmt->fetch(); return $row ?: null;
  }
  private function ownedId(string $table,string $column,?int $id,int $uid): ?int {
    if (!$id) return null;
    return $this->one("SELECT {$column} FROM {$table} WHERE {$column}=? AND user_id=?",[$id,$uid]) ? $id : null;
  }
  private function ownedApplication(int $uid,int $companyId,?int $applicationId): ?int {
    if (!$applicationId) return null;
    $row=$this->one('SELECT application_id FROM applications WHERE application_id=? AND user_id=? AND company_id=?',[$applicationId,$uid,$companyId]);
    if (!$row) throw new RuntimeException('応募案件が見つかりません。');
    return $applicationId;
  }
  private function syncNoteTags(int $uid,int $noteId,array $tagIds): void {
    $this->pdo->prepare('DELETE FROM note_tag_links WHERE note_id=?')->execute([$noteId]);
    $stmt=$this->pdo->prepare('INSERT INTO note_tag_links (note_id,tag_id) SELECT ?,tag_id FROM note_tags WHERE tag_id=? AND user_id=?');
    foreach($tagIds as $tagId)if((int)$tagId>0)$stmt->execute([$noteId,(int)$tagId,$uid]);
  }
  private function recordState(int $uid,string $type,int $id,string $action,string $summary): void {
    $this->pdo->prepare('INSERT INTO state_history (user_id,entity_type,entity_id,action,summary) VALUES (?,?,?,?,?)')->execute([$uid,$type,$id,$action,$summary]);
  }
}
