<?php
declare(strict_types=1);
require dirname(__DIR__) . '/public/lib/app.php';
if (PHP_SAPI !== 'cli') exit("CLI only\n");
$sqlitePath = $argv[1] ?? private_root() . '/app.sqlite';
$source = new PDO('sqlite:' . $sqlitePath);
$target = db();
$checks = ['users'=>'users','companies'=>'companies','flow_nodes'=>'flow_steps','events'=>'events','tasks'=>'tasks','interview_logs'=>'interview_logs','notes'=>'notes'];
$failed = false;
foreach ($checks as $old => $new) {
  $a = (int)$source->query("SELECT COUNT(*) FROM {$old}")->fetchColumn();
  $b = (int)$target->query("SELECT COUNT(*) FROM {$new}")->fetchColumn();
  echo "{$old} -> {$new}: {$a} / {$b}\n";
  if ($a !== $b) $failed = true;
}
$companies=(int)$target->query('SELECT COUNT(*) FROM companies')->fetchColumn();
$applications=(int)$target->query('SELECT COUNT(*) FROM applications')->fetchColumn();
echo "companies -> default applications: {$companies} / {$applications}\n";
if ($applications < $companies) $failed=true;
exit($failed ? 1 : 0);
