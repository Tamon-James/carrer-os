<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$errors=[];

$required=[
  'database/migrations/001_initial_mysql.sql',
  'database/install_mysql.sql',
  'database/migrate_sqlite.php',
  'src/Repositories/CareerRepository.php',
  'src/Services/ResourceService.php',
  'public/api/interview-draft.php',
  'public/assets/css/base.css',
  'public/assets/css/components.css',
  'public/assets/css/layout.css',
  'public/assets/js/core.js',
  'public/assets/js/interview.js',
  'public/assets/js/calendar.js',
  'public/guide.php',
  'public/assets/css/guide.css',
];
foreach($required as $file) if(!is_file($root.'/'.$file)) $errors[]="Missing {$file}";

$schema=(string)file_get_contents($root.'/database/migrations/001_initial_mysql.sql');
foreach(['applications','flow_steps','events','tasks','notes','contents','interview_drafts','interview_logs','resources','legacy_records'] as $table) {
  if(!str_contains($schema,"CREATE TABLE {$table}")) $errors[]="Schema table missing: {$table}";
}
foreach(['ENGINE=InnoDB','DEFAULT CHARSET=utf8mb4','ON DELETE CASCADE'] as $marker) {
  if(!str_contains($schema,$marker)) $errors[]="Schema marker missing: {$marker}";
}
foreach([
  'views/dashboard.php'=>'add_task',
  'views/company.php'=>'complete_task',
  'views/interview.php'=>'data-toggle-prep',
  'views/interview.php'=>'data-toggle-scheduler',
  'public/campe.php'=>'add_tentative_event',
  'public/assets/js/interview.js'=>'data-save-now',
  'views/dashboard.php'=>'find_availability',
  'database/migrations/002_calendar_scheduling.sql'=>'schedule_status',
  'views/guide.php'=>'company-workspace.png',
  'views/guide.php'=>'ホーム画面に追加',
  'views/guide.php'=>'Ctrl + D',
  'public/index.php'=>"redirect('guide.php')",
  'views/layout/start.php'=>"public_root().'/partials/header.php'",
] as $file=>$marker) {
  if(!str_contains((string)file_get_contents($root.'/'.$file),$marker)) $errors[]="Feature marker missing: {$marker}";
}

$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/public'));
foreach($iterator as $file) {
  if(!$file->isFile()||$file->getExtension()!=='php') continue;
  $body=(string)file_get_contents($file->getPathname());
  foreach(["datetime('now')",'INSERT OR ','ON CONFLICT','sqlite:','<style>'] as $legacy) {
    if(str_contains($body,$legacy)) $errors[]="Legacy marker {$legacy} in ".$file->getFilename();
  }
}

if($errors) {
  fwrite(STDERR,implode(PHP_EOL,$errors).PHP_EOL);
  exit(1);
}
echo "Static smoke checks passed.\n";
