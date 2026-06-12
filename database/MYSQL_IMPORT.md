# MySQLインポート手順

PHPコマンドを実行できない環境では、`install_mysql.sql`をphpMyAdminからインポートしてください。

## 新規DBへインポートする

1. サーバー管理画面でMySQLデータベースを作成する
2. 任意のデータベース名（例: `career_ops`）で作成する
3. phpMyAdminへログインする
4. 上部メニューの「インポート」を開く
5. `database/install_mysql.sql`を選択する
6. 文字コードを`utf-8`にして実行する

インポート後、21テーブルが作成されていれば完了です。

## アプリの接続先

`Carrer_site_private/config.php`へ、作成したMySQLデータベースの接続情報を設定します。

```php
<?php
return [
  'db_host' => 'localhost',
  'db_name' => '作成したDB名',
  'db_user' => 'DBユーザー名',
  'db_password' => 'DBパスワード',
  'db_charset' => 'utf8mb4',
  'session_name' => 'career_ops_sid',
  'upload_dir' => __DIR__ . '/uploads',
];
```

## 注意

- `install_mysql.sql`は新規・空DB用です。
- SQL先頭の`USE career_ops;`を、作成したDB名に書き換えてください。
- 実行すると同名テーブルを削除して作り直します。既存データがあるDBへ実行しないでください。
- テストアカウントやデモデータは含まれていません。
- 旧`app.sqlite`のデータは含まれていません。
