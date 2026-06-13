<?php
declare(strict_types=1);
require dirname(__DIR__).'/public/lib/app.php';
if(PHP_SAPI!=='cli')exit("CLI only\n");
$pdo=db();
$requiredTables=['note_tags','note_tag_links','note_groups','note_agents','note_versions','agent_company_links','session_types','state_history','tutorial_records'];
$failed=false;
foreach($requiredTables as $table){
  $stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
  $stmt->execute([$table]);
  $ok=(int)$stmt->fetchColumn()===1;
  echo ($ok?'[OK] ':'[NG] ')."table {$table}\n";
  $failed=$failed||!$ok;
}
foreach([['flow_steps','company_id'],['flow_steps','application_id'],['notes','interview_visible'],['notes','deleted_at'],['interview_logs','agent_id'],['users','tutorial_status']] as [$table,$column]){
  $stmt=$pdo->prepare('SELECT IS_NULLABLE FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');
  $stmt->execute([$table,$column]);$value=$stmt->fetchColumn();
  $ok=$value!==false;
  echo ($ok?'[OK] ':'[NG] ')."column {$table}.{$column}".($value!==false?" nullable={$value}":'')."\n";
  $failed=$failed||!$ok;
}
$orphan=(int)$pdo->query('SELECT COUNT(*) FROM flow_steps WHERE company_id IS NULL')->fetchColumn();
echo ($orphan===0?'[OK] ':'[NG] ')."flow steps without company: {$orphan}\n";
$contents=(int)$pdo->query('SELECT COUNT(*) FROM contents')->fetchColumn();
$converted=(int)$pdo->query("SELECT COUNT(*) FROM notes WHERE interview_visible=1")->fetchColumn();
echo "[INFO] legacy contents: {$contents}, interview-visible unified notes: {$converted}\n";
exit($failed||$orphan>0?1:0);
