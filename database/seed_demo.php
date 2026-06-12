<?php
declare(strict_types=1);
require dirname(__DIR__) . '/public/lib/app.php';

if (PHP_SAPI !== 'cli') exit("CLI only\n");

$pdo=db();
$email=$argv[1]??'';
if($email==='')throw new InvalidArgumentException('Usage: php database/seed_demo.php user@example.com');
$stmt=$pdo->prepare('SELECT user_id FROM users WHERE email=?');
$stmt->execute([$email]);
$uid=(int)$stmt->fetchColumn();
if(!$uid) throw new RuntimeException("User not found: {$email}");

$pdo->beginTransaction();
try {
  $pdo->prepare("DELETE FROM companies WHERE user_id=? AND memo='CAREER_OS_DEMO'")->execute([$uid]);
  $pdo->prepare("DELETE FROM contents WHERE user_id=? AND company_id IS NULL AND title LIKE '[デモ]%'")->execute([$uid]);
  $pdo->prepare("DELETE FROM events WHERE user_id=? AND memo='デモ予定'")->execute([$uid]);

  $at=static fn(int $days,string $time): string => (new DateTimeImmutable('today'))->modify(($days>=0?'+':'').$days.' days')->format('Y-m-d').' '.$time;

  $master=function(string $table,string $column,string $name,int $sort) use($pdo,$uid): int {
    $stmt=$pdo->prepare("SELECT {$column} FROM {$table} WHERE user_id=? AND name=?");
    $stmt->execute([$uid,$name]);
    $id=(int)$stmt->fetchColumn();
    if($id)return $id;
    $pdo->prepare("INSERT INTO {$table} (user_id,name,sort_order) VALUES (?,?,?)")->execute([$uid,$name,$sort]);
    return (int)$pdo->lastInsertId();
  };
  $industries=[
    'SaaS・IT'=>$master('industries','industry_id','SaaS・IT',10),
    'コンサルティング'=>$master('industries','industry_id','コンサルティング',20),
    '広告・メディア'=>$master('industries','industry_id','広告・メディア',30),
    'FinTech'=>$master('industries','industry_id','FinTech',40),
  ];
  $jobs=[
    'プロダクト企画'=>$master('job_categories','job_category_id','プロダクト企画',10),
    'ITコンサルタント'=>$master('job_categories','job_category_id','ITコンサルタント',20),
    '法人営業'=>$master('job_categories','job_category_id','法人営業',30),
    'ソフトウェアエンジニア'=>$master('job_categories','job_category_id','ソフトウェアエンジニア',40),
  ];
  $interests=[
    '第一志望群'=>$master('interest_levels','interest_level_id','第一志望群',10),
    '志望度高'=>$master('interest_levels','interest_level_id','志望度高',20),
    '検討中'=>$master('interest_levels','interest_level_id','検討中',30),
  ];
  $statuses=[
    '書類選考中'=>$master('statuses','status_id','書類選考中',10),
    '面接調整中'=>$master('statuses','status_id','面接調整中',20),
    '一次面接通過'=>$master('statuses','status_id','一次面接通過',30),
    '最終面接予定'=>$master('statuses','status_id','最終面接予定',40),
  ];
  $sources=[
    '企業採用サイト'=>$master('application_sources','source_id','企業採用サイト',10),
    '就活エージェント'=>$master('application_sources','source_id','就活エージェント',20),
    'スカウト'=>$master('application_sources','source_id','スカウト',30),
  ];

  $companyStmt=$pdo->prepare('INSERT INTO companies (user_id,name,industry_id,corporate_url,business,memo,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
  $applicationStmt=$pdo->prepare('INSERT INTO applications (user_id,company_id,title,job_category_id,interest_level_id,status_id,source_id,mypage_url,motivation,next_action,deadline_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
  $stepStmt=$pdo->prepare('INSERT INTO flow_steps (user_id,application_id,title,step_type,status,sort_order,scheduled_at,deadline_at,url,memo) VALUES (?,?,?,?,?,?,?,?,?,?)');
  $eventStmt=$pdo->prepare("INSERT INTO events (user_id,company_id,application_id,event_type,schedule_status,title,start_at,end_at,location,url,memo,state) VALUES (?,?,?,?,?,?,?,?,?,?,?,'active')");
  $taskStmt=$pdo->prepare('INSERT INTO tasks (user_id,company_id,application_id,title,due_at,done) VALUES (?,?,?,?,?,?)');
  $noteStmt=$pdo->prepare('INSERT INTO notes (user_id,company_id,application_id,category,title,body) VALUES (?,?,?,?,?,?)');
  $contentStmt=$pdo->prepare('INSERT INTO contents (user_id,company_id,application_id,category,title,body,interview_visible,sort_order) VALUES (?,?,?,?,?,?,?,?)');
  $resourceStmt=$pdo->prepare("INSERT INTO resources (user_id,company_id,application_id,resource_type,category,display_name,external_url) VALUES (?,?,?,'external',?,?,?)");
  $logStmt=$pdo->prepare('INSERT INTO interview_logs (user_id,company_id,application_id,title,body,occurred_at) VALUES (?,?,?,?,?,?)');

  $companies=[
    ['株式会社ノーススター','SaaS・IT','https://example.com/northstar','企業向け業務効率化SaaSを提供。顧客の業務設計から定着支援まで一貫して伴走する。','プロダクト企画職','プロダクト企画','第一志望群','一次面接通過','企業採用サイト','顧客課題を起点にプロダクトを改善する文化と、若手にも意思決定を任せる環境に魅力を感じた。','二次面接に向けて競合比較を整理',$at(7,'18:00:00')],
    ['アーク戦略パートナーズ株式会社','コンサルティング','https://example.com/ark','DX戦略、新規事業、業務改革を支援する総合コンサルティングファーム。','ITコンサルタント職','ITコンサルタント','第一志望群','最終面接予定','就活エージェント','業務とITの両面から変革を実行し、構想だけで終わらせない支援スタイルに共感した。','最終面接の逆質問を磨く',$at(3,'12:00:00')],
    ['ブルームメディア株式会社','広告・メディア','https://example.com/bloom','ブランド戦略とデジタルマーケティングを組み合わせ、企業の成長を支援。','法人営業職','法人営業','志望度高','面接調整中','スカウト','顧客と長期的に向き合い、企画から効果検証まで担当できる点に惹かれた。','一次面接候補日を返信',$at(1,'17:00:00')],
    ['ミライペイメント株式会社','FinTech','https://example.com/mirai-pay','中小事業者向け決済・請求管理サービスを開発。','ソフトウェアエンジニア職','ソフトウェアエンジニア','検討中','書類選考中','企業採用サイト','決済基盤の信頼性とユーザー体験を両立する技術課題に取り組みたい。','技術課題を提出',$at(9,'23:59:00')],
  ];

  $ids=[];
  foreach($companies as $index=>$company){
    [$name,$industry,$url,$business,$title,$job,$interest,$status,$source,$motivation,$next,$deadline]=$company;
    $companyStmt->execute([$uid,$name,$industries[$industry],$url,$business,'CAREER_OS_DEMO']);
    $companyId=(int)$pdo->lastInsertId();
    $applicationStmt->execute([$uid,$companyId,$title,$jobs[$job],$interests[$interest],$statuses[$status],$sources[$source],$url.'/mypage',$motivation,$next,$deadline]);
    $applicationId=(int)$pdo->lastInsertId();
    $ids[]=['company'=>$companyId,'application'=>$applicationId,'name'=>$name,'title'=>$title];

    $flows=[
      ['会社説明会','briefing','done',10,'2026-05-20 15:00:00',null],
      ['ES・履歴書提出','es',$index<3?'done':'doing',20,null,$index===3?$at(9,'23:59:00'):$at(-14,'23:59:00')],
      ['適性検査','test',$index<2?'done':'todo',30,null,$at(2,'23:59:00')],
      ['一次面接','interview',$index<2?'done':($index===2?'doing':'todo'),40,$index===2?$at(5,'14:00:00'):$at(-6,'11:00:00'),null],
      ['最終面接','interview',$index===1?'doing':'todo',50,$index===1?$at(4,'10:00:00'):null,null],
      ['内定・条件面談','offer','todo',60,null,null],
    ];
    foreach($flows as [$flowTitle,$type,$flowStatus,$sort,$scheduled,$flowDeadline])$stepStmt->execute([$uid,$applicationId,$flowTitle,$type,$flowStatus,$sort,$scheduled,$flowDeadline,null,$flowStatus==='doing'?'準備を優先する':'']);

    $noteStmt->execute([$uid,$companyId,$applicationId,'company_research','企業研究まとめ',"事業の強み\n・継続率が高く、既存顧客からの紹介が多い\n・現場起点で改善を進める文化\n\n確認したいこと\n・入社後半年間の期待役割\n・評価指標とチーム構成"]);
    $contentStmt->execute([$uid,$companyId,$applicationId,'motivation','志望動機',$motivation."\n\n入社後は、利用者の声を定量・定性の両面から捉え、改善を継続できる人材として貢献したい。",1,30]);
    $contentStmt->execute([$uid,$companyId,$applicationId,'questions','企業別の逆質問',"・現在チームが最も重視している課題は何ですか。\n・活躍している若手社員に共通する行動は何ですか。\n・入社前に学んでおくべきことを教えてください。",1,40]);
    $resourceStmt->execute([$uid,$companyId,$applicationId,'company_material','企業説明会資料',$url.'/drive/company-material']);
  }

  $globalContents=[
    ['self_pr','[デモ] 自己PR','私の強みは、曖昧な課題を整理し、周囲を巻き込みながら改善を実行する力です。ゼミ活動では情報共有の仕組みを見直し、準備時間を30%削減しました。',10],
    ['gakuchika','[デモ] ガクチカ','学生団体のイベント運営で参加率向上に取り組みました。参加者へのヒアリングから課題を特定し、告知内容と申込導線を改善した結果、前年の1.5倍の参加者を集めました。',20],
    ['questions','[デモ] 共通逆質問',"・入社後3か月で期待される状態を教えてください。\n・意思決定の際に大切にしている価値観は何ですか。\n・今後強化したい事業領域はどこですか。",30],
  ];
  foreach($globalContents as [$category,$title,$body,$sort])$contentStmt->execute([$uid,null,null,$category,$title,$body,1,$sort]);

  [$north,$ark,$bloom,$mirai]=$ids;
  $events=[
    [$north,'other','confirmed','ノーススター 面接振り返り・次回準備',$at(0,'19:00:00'),$at(0,'19:45:00'),'自宅'],
    [$ark,'interview','confirmed','アーク戦略 最終面接',$at(4,'10:00:00'),$at(4,'11:00:00'),'Google Meet'],
    [$bloom,'interview','tentative','ブルームメディア 一次面接候補',$at(5,'14:00:00'),$at(5,'15:00:00'),'Google Meet'],
    [$north,'interview','confirmed','ノーススター 二次面接',$at(7,'13:00:00'),$at(7,'14:00:00'),'オンライン'],
    [$mirai,'deadline','confirmed','技術課題 提出締切',$at(9,'23:00:00'),$at(9,'23:59:00'),''],
    [null,'personal','confirmed','大学ゼミ',$at(6,'10:30:00'),$at(6,'12:00:00'),'大学'],
    [null,'personal','confirmed','面接練習',$at(2,'16:00:00'),$at(2,'17:00:00'),'オンライン'],
  ];
  foreach($events as [$scope,$type,$scheduleStatus,$title,$start,$end,$location])$eventStmt->execute([$uid,$scope['company']??null,$scope['application']??null,$type,$scheduleStatus,$title,$start,$end,$location,null,'デモ予定']);

  $tasks=[
    [$ark,'最終面接用の逆質問を3つに絞る',$at(2,'20:00:00'),0],
    [$bloom,'一次面接の候補日を返信する',$at(1,'17:00:00'),0],
    [$north,'競合サービス3社を比較する',$at(6,'18:00:00'),0],
    [$mirai,'技術課題のREADMEを仕上げる',$at(9,'20:00:00'),0],
    [$north,'説明会メモを整理する',$at(-1,'20:00:00'),1],
  ];
  foreach($tasks as [$scope,$title,$due,$done])$taskStmt->execute([$uid,$scope['company'],$scope['application'],$title,$due,$done]);

  $logStmt->execute([$uid,$north['company'],$north['application'],'ノーススター 一次面接ログ',"質問：なぜプロダクト企画なのか\n回答：顧客課題を構造化し、改善を継続する仕事に魅力を感じるため。\n\n次回改善：具体的な数値成果を先に伝える。",$at(-6,'11:00:00')]);
  $pdo->prepare('INSERT INTO interview_drafts (user_id,company_id,application_id,draft_scope,title,body) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),body=VALUES(body)')
    ->execute([$uid,$ark['company'],$ark['application'],'application:'.$ark['application'],'アーク戦略 最終面接ログ',"確認したいこと\n・配属プロジェクトの決まり方\n・若手が担当した変革事例\n\n面接中メモ\n"]);

  $pdo->commit();
  echo "Demo data seeded for {$email}\n";
  foreach($ids as $row)echo "{$row['company']}\t{$row['application']}\t{$row['name']}\n";
} catch(Throwable $e) {
  $pdo->rollBack();
  throw $e;
}
