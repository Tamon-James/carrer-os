<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if (current_user_id()!==null) redirect('index.php');
$error='';
if (($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
  csrf_verify();
  $stmt=db()->prepare('SELECT user_id,password_hash FROM users WHERE email=? LIMIT 1');
  $stmt->execute([post_string('email')]); $user=$stmt->fetch();
  if (!$user||!password_verify(post_string('password'),$user['password_hash'])) $error='メールアドレスまたはパスワードが違います。';
  else { app_start_session(); session_regenerate_id(true); $_SESSION['user_id']=(int)$user['user_id']; redirect('index.php'); }
}
render('auth',['page_title'=>'ログイン','mode'=>'login','error'=>$error]);

