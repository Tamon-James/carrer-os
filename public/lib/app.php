<?php
declare(strict_types=1);

const APP_TIMEZONE = 'Asia/Tokyo';
const APP_MAX_UPLOAD_BYTES = 10 * 1024 * 1024;

date_default_timezone_set(APP_TIMEZONE);

function app_root(): string {
  return dirname(dirname(__DIR__));
}

function public_root(): string {
  return dirname(__DIR__);
}

function private_root(): string {
  return app_root() . DIRECTORY_SEPARATOR . 'Carrer_site_private';
}

function app_config(): array {
  static $config;
  if (is_array($config)) return $config;
  $path = private_root() . DIRECTORY_SEPARATOR . 'config.php';
  $config = is_file($path) ? (array)require $path : [];
  return $config;
}

function config_value(string $key, mixed $default = null): mixed {
  $env = getenv(strtoupper($key));
  if ($env !== false && $env !== '') return $env;
  return app_config()[$key] ?? $default;
}

function db(): PDO {
  static $pdo;
  if ($pdo instanceof PDO) return $pdo;

  $host = (string)config_value('db_host', '127.0.0.1');
  $port = (string)config_value('db_port', '3306');
  $name = (string)config_value('db_name', 'career_ops');
  $charset = (string)config_value('db_charset', 'utf8mb4');
  $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

  $pdo = new PDO($dsn, (string)config_value('db_user', 'career_ops'), (string)config_value('db_password', ''), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
  $pdo->exec("SET time_zone = '+09:00'");
  return $pdo;
}

function app_start_session(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  session_name((string)config_value('session_name', 'career_ops_sid'));
  session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => '/',
  ]);
  session_start();
}

function current_user_id(): ?int {
  app_start_session();
  $value = $_SESSION['user_id'] ?? null;
  if (is_int($value)) return $value;
  return is_string($value) && ctype_digit($value) ? (int)$value : null;
}

function require_login(): int {
  $uid = current_user_id();
  if ($uid === null) redirect('login.php');
  $stmt = db()->prepare('SELECT 1 FROM users WHERE user_id = ? LIMIT 1');
  $stmt->execute([$uid]);
  if (!$stmt->fetchColumn()) {
    $_SESSION = [];
    redirect('login.php');
  }
  return $uid;
}

function csrf_token(): string {
  app_start_session();
  if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  return (string)$_SESSION['csrf_token'];
}

function csrf_verify(): void {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') return;
  app_start_session();
  $token = (string)($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
  if ($token === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
    http_response_code(403);
    exit('CSRF token mismatch.');
  }
}

function h(mixed $value): string {
  return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function qp(string $key, string $default = ''): string {
  $value = $_GET[$key] ?? $default;
  return is_string($value) ? $value : $default;
}

function post_string(string $key, string $default = ''): string {
  $value = $_POST[$key] ?? $default;
  return is_string($value) ? trim($value) : $default;
}

function post_id(string $key): ?int {
  $value = $_POST[$key] ?? '';
  return is_string($value) && ctype_digit($value) && (int)$value > 0 ? (int)$value : null;
}

function is_https_url(string $url): bool {
  return filter_var($url, FILTER_VALIDATE_URL) !== false && str_starts_with(strtolower($url), 'https://');
}

function redirect(string $to): never {
  header('Location: ' . $to);
  exit;
}

function flash(string $type, string $message): void {
  app_start_session();
  $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flashes(): array {
  app_start_session();
  $items = is_array($_SESSION['flash'] ?? null) ? $_SESSION['flash'] : [];
  unset($_SESSION['flash']);
  return $items;
}

function json_response(array $data, int $status = 200): never {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function render(string $view, array $data = []): void {
  extract($data, EXTR_SKIP);
  require app_root() . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $view . '.php';
}

function fmt_datetime(?string $value): string {
  return $value ? substr(str_replace('T', ' ', $value), 0, 16) : '-';
}

function sql_datetime(string $value): ?string {
  if ($value === '') return null;
  $value = str_replace('T', ' ', $value);
  $date = DateTimeImmutable::createFromFormat('Y-m-d H:i', $value);
  return $date ? $date->format('Y-m-d H:i:00') : null;
}

function ensure_user_defaults(int $uid): void {
  $pdo = db();
  $defaults = [
    'statuses' => ['検討中', '応募済み', '選考中', '内定', '見送り'],
    'industries' => ['IT・Web', 'メーカー', 'コンサルティング', '金融', 'その他'],
    'job_categories' => ['エンジニア', '営業', '企画', 'コンサルタント', 'その他'],
    'interest_levels' => ['第一志望', '高', '中', '低'],
    'application_sources' => ['企業サイト', '求人媒体', 'エージェント', 'スカウト', 'その他'],
  ];
  foreach ($defaults as $table => $names) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = ?");
    $check->execute([$uid]);
    if ((int)$check->fetchColumn() > 0) continue;
    $insert = $pdo->prepare("INSERT INTO {$table} (user_id, name, sort_order) VALUES (?, ?, ?)");
    foreach ($names as $index => $name) $insert->execute([$uid, $name, $index * 10]);
  }
  foreach ([
    'note_tags'=>['企業研究','説明会','志望動機','逆質問','自己PR','ガクチカ','面接練習'],
    'session_types'=>['企業面接','エージェント面談','面接練習','説明会','就活相談'],
  ] as $table=>$names) {
    try {
      $check=$pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id=?");
      $check->execute([$uid]);
      if((int)$check->fetchColumn()>0)continue;
      $insert=$pdo->prepare("INSERT INTO {$table} (user_id,name,sort_order) VALUES (?,?,?)");
      foreach($names as $index=>$name)$insert->execute([$uid,$name,($index+1)*10]);
    } catch(PDOException) {
      // Allows login before the optional operations-v2 migration is applied.
    }
  }
}

function delete_tutorial_data(int $uid): void {
  $pdo=db();
  $ownsTransaction=!$pdo->inTransaction();
  if($ownsTransaction)$pdo->beginTransaction();
  try{
    $stmt=$pdo->prepare("SELECT entity_id FROM tutorial_records WHERE user_id=? AND entity_type='company'");
    $stmt->execute([$uid]);
    $companyIds=array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN));
    $deleteCompany=$pdo->prepare('DELETE FROM companies WHERE company_id=? AND user_id=?');
    foreach($companyIds as $companyId)$deleteCompany->execute([$companyId,$uid]);
    $pdo->prepare('DELETE FROM tutorial_records WHERE user_id=?')->execute([$uid]);
    if($ownsTransaction)$pdo->commit();
  }catch(Throwable $e){
    if($ownsTransaction&&$pdo->inTransaction())$pdo->rollBack();
    throw $e;
  }
}

function purge_expired_text_trash(int $uid): void {
  try {
    db()->prepare("DELETE FROM notes WHERE user_id=? AND deleted_at IS NOT NULL AND deleted_at<DATE_SUB(NOW(),INTERVAL 30 DAY)")->execute([$uid]);
  } catch(PDOException) {
    // The trash feature is available after migration 003.
  }
}
