<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if (current_user_id()!==null) redirect('index.php');
$error='';
if (($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
  csrf_verify(); $email=post_string('email'); $password=post_string('password');
  if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $error='メールアドレスを正しく入力してください。';
  elseif (mb_strlen($password)<8) $error='パスワードは8文字以上にしてください。';
  elseif ($password!==post_string('password2')) $error='確認用パスワードが一致しません。';
  else {
    try {
      $stmt=db()->prepare('INSERT INTO users (email,password_hash) VALUES (?,?)'); $stmt->execute([$email,password_hash($password,PASSWORD_DEFAULT)]);
      app_start_session(); $_SESSION['user_id']=(int)db()->lastInsertId(); redirect('tutorial.php');
    } catch(PDOException) { $error='このメールアドレスは既に登録されています。'; }
  }
}
render('auth',['page_title'=>'ユーザー登録','mode'=>'register','error'=>$error]);
