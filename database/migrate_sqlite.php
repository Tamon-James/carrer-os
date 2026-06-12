<?php
declare(strict_types=1);
require dirname(__DIR__) . '/public/lib/app.php';

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$sqlitePath = $argv[1] ?? private_root() . '/app.sqlite';
if (!is_file($sqlitePath)) exit("SQLite file not found: {$sqlitePath}\n");
$source = new PDO('sqlite:' . $sqlitePath, null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$target = db();

function rows(PDO $pdo, string $table): array {
  try { return $pdo->query("SELECT * FROM {$table}")->fetchAll(); } catch (Throwable) { return []; }
}
function insert_row(PDO $pdo, string $table, array $row): void {
  $columns = array_keys($row);
  $quoted = array_map(fn(string $c): string => "`{$c}`", $columns);
  $sql = "INSERT INTO `{$table}` (" . implode(',', $quoted) . ') VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')';
  $pdo->prepare($sql)->execute(array_values($row));
}

$target->beginTransaction();
try {
  foreach (rows($source, 'users') as $row) insert_row($target, 'users', $row);
  foreach (rows($source, 'statuses') as $row) insert_row($target, 'statuses', $row);
  foreach (rows($source, 'sources') as $row) insert_row($target, 'application_sources', $row);
  foreach (rows($source, 'agents') as $row) insert_row($target, 'agents', $row);

  $defaultIndustry = [];
  $defaultJob = [];
  $defaultInterest = [];
  foreach (rows($source, 'users') as $user) {
    $uid = (int)$user['user_id'];
    $target->prepare("INSERT INTO industries (user_id,name,sort_order) VALUES (?,'未設定',0)")->execute([$uid]);
    $defaultIndustry[$uid] = (int)$target->lastInsertId();
    $target->prepare("INSERT INTO job_categories (user_id,name,sort_order) VALUES (?,'未設定',0)")->execute([$uid]);
    $defaultJob[$uid] = (int)$target->lastInsertId();
    $target->prepare("INSERT INTO interest_levels (user_id,name,sort_order) VALUES (?,'未設定',0)")->execute([$uid]);
    $defaultInterest[$uid] = (int)$target->lastInsertId();
  }

  $applicationMap = [];
  foreach (rows($source, 'companies') as $row) {
    $uid = (int)$row['user_id'];
    insert_row($target, 'companies', [
      'company_id' => $row['company_id'], 'user_id' => $uid, 'name' => $row['name'],
      'industry_id' => $defaultIndustry[$uid], 'business' => $row['business'] ?? null,
      'memo' => $row['memo'] ?? null, 'archived' => $row['rejected_flag'] ?? 0,
      'created_at' => $row['created_at'], 'updated_at' => $row['updated_at'],
    ]);
    $target->prepare('INSERT INTO applications (user_id,company_id,title,job_category_id,interest_level_id,status_id,source_id,agent_id,mypage_url,rejected,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
      ->execute([$uid,$row['company_id'],'既定応募',$defaultJob[$uid],$defaultInterest[$uid],$row['status_id'] ?? null,$row['source_id'] ?? null,$row['agent_id'] ?? null,$row['mypage_url'] ?? null,$row['rejected_flag'] ?? 0,$row['created_at'],$row['updated_at']]);
    $applicationMap[(int)$row['company_id']] = (int)$target->lastInsertId();
  }

  foreach (rows($source, 'flow_nodes') as $row) {
    $applicationId = $applicationMap[(int)$row['company_id']] ?? null;
    if (!$applicationId) continue;
    insert_row($target, 'flow_steps', [
      'step_id'=>$row['node_id'],'user_id'=>$row['user_id'],'application_id'=>$applicationId,'title'=>$row['title'],
      'step_type'=>$row['node_type'],'status'=>$row['status'],'sort_order'=>$row['sort_order'],'url'=>$row['url'],
      'memo'=>$row['memo'],'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at'],
    ]);
  }
  foreach (rows($source, 'interview_logs') as $row) {
    insert_row($target, 'interview_logs', [
      'log_id'=>$row['log_id'],'user_id'=>$row['user_id'],'company_id'=>$row['company_id'],
      'application_id'=>$row['company_id'] ? ($applicationMap[(int)$row['company_id']] ?? null) : null,
      'title'=>$row['title'],'body'=>$row['body'],'occurred_at'=>$row['occurred_at'],
      'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at'],
    ]);
  }
  foreach (rows($source, 'events') as $row) {
    $companyId=$row['company_id'] ?? null;
    insert_row($target, 'events', [
      'event_id'=>$row['event_id'],'user_id'=>$row['user_id'],'company_id'=>$companyId,
      'application_id'=>$companyId ? ($applicationMap[(int)$companyId] ?? null) : null,
      'agent_id'=>$row['agent_id'] ?? null,'step_id'=>$row['node_id'] ?? null,'event_type'=>$row['event_type'] ?? 'other','title'=>$row['title'],
      'start_at'=>$row['start_at'],'end_at'=>$row['end_at'] ?? null,'location'=>$row['location'] ?? null,
      'url'=>$row['url'] ?? null,'memo'=>$row['memo'] ?? null,
      'state'=>!empty($row['completed'])?'completed':(($row['cancel_state']??'active')==='active'?'active':'cancelled'),
      'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at'],
    ]);
  }
  foreach (rows($source, 'tasks') as $row) {
    $companyId=$row['company_id'] ?? null;
    insert_row($target,'tasks',[
      'task_id'=>$row['task_id'],'user_id'=>$row['user_id'],'company_id'=>$companyId,
      'application_id'=>$companyId ? ($applicationMap[(int)$companyId]??null) : null,
      'step_id'=>$row['node_id']??null,'title'=>$row['title'],'due_at'=>$row['due_at']??null,
      'done'=>$row['done']??0,'hidden'=>$row['hidden']??0,'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at'],
    ]);
  }
  foreach (rows($source, 'notes') as $row) {
    $companyId=$row['company_id'] ?? null;
    insert_row($target, 'notes', [
      'note_id'=>$row['note_id'],'user_id'=>$row['user_id'],'company_id'=>$companyId,
      'application_id'=>$companyId ? ($applicationMap[(int)$companyId] ?? null) : null,
      'agent_id'=>$row['agent_id'] ?? null,'category'=>$row['kind'] ?? 'company_research',
      'title'=>$row['kind'] ?? 'メモ','body'=>$row['body'],'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at'],
    ]);
  }
  foreach (rows($source, 'cheat_sheets') as $row) {
    $companyId=$row['company_id'] ?? null;
    insert_row($target, 'contents', [
      'user_id'=>$row['user_id'],'company_id'=>$companyId,
      'application_id'=>$companyId ? ($applicationMap[(int)$companyId] ?? null) : null,
      'category'=>'cheat','title'=>$row['title'],'body'=>$row['content'],'interview_visible'=>$row['is_active'],
      'sort_order'=>$row['sort_order'],'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at'],
    ]);
  }
  foreach (rows($source, 'flow_templates') as $row) {
    insert_row($target,'flow_templates',['template_id'=>$row['template_id'],'user_id'=>$row['user_id'],'name'=>$row['name'],'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at']]);
    $steps=json_decode((string)$row['json'],true);
    if (!is_array($steps)) continue;
    foreach ($steps as $index=>$step) {
      if (!is_array($step) || empty($step['title'])) continue;
      insert_row($target,'flow_template_steps',['template_id'=>$row['template_id'],'title'=>$step['title'],'step_type'=>$step['type']??'step','sort_order'=>$index*10]);
    }
  }
  foreach (rows($source, 'company_events') as $row) {
    insert_row($target,'notes',['user_id'=>$row['user_id'],'company_id'=>$row['company_id'],'application_id'=>$applicationMap[(int)$row['company_id']]??null,'category'=>'company_history','title'=>$row['title'],'body'=>$row['body'],'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at']]);
  }
  foreach (rows($source, 'agent_events') as $row) {
    insert_row($target,'notes',['user_id'=>$row['user_id'],'agent_id'=>$row['agent_id'],'category'=>'agent','title'=>$row['title'],'body'=>$row['body'],'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at']]);
  }
  foreach (['status_templates','flow_node_templates','snippets','node_task_settings','interview_tags','interview_log_tags','interview_log_deletes'] as $legacyTable) {
    foreach (rows($source,$legacyTable) as $row) {
      $sourceId=$row[array_key_first($row)]??null;
      $target->prepare('INSERT INTO legacy_records (user_id,source_table,source_id,payload) VALUES (?,?,?,?)')->execute([$row['user_id']??null,$legacyTable,$sourceId,json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
    }
  }
  foreach (rows($source, 'snippets2') as $row) {
    insert_row($target, 'contents', [
      'user_id'=>$row['user_id'],'company_id'=>$row['company_id'],
      'application_id'=>$row['company_id'] ? ($applicationMap[(int)$row['company_id']] ?? null) : null,
      'category'=>in_array($row['category'], ['self_pr','gakuchika','motivation','questions','other'], true) ? $row['category'] : 'other',
      'title'=>$row['title'],'body'=>$row['content'],'interview_visible'=>$row['is_active'],'sort_order'=>$row['sort_order'],
      'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at'],
    ]);
  }
  $target->commit();
  echo "Migration completed.\n";
} catch (Throwable $e) {
  $target->rollBack();
  fwrite(STDERR, $e->getMessage() . "\n");
  exit(1);
}
