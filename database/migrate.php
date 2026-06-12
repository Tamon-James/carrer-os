<?php
declare(strict_types=1);
require dirname(__DIR__) . '/public/lib/app.php';

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$pdo = db();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(255) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)');
$applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
foreach (glob(__DIR__ . '/migrations/*.sql') ?: [] as $file) {
  $version = basename($file);
  if (in_array($version, $applied, true)) continue;
  $pdo->exec((string)file_get_contents($file));
  $stmt = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
  $stmt->execute([$version]);
  echo "Applied {$version}\n";
}

