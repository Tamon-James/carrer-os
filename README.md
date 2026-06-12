# Career OS

就活中に必要な情報を一か所へ集約する、個人向け就活オペレーションシステムです。

企業・応募案件・選考フロー・予定・締切・面接対策・メモをまとめて管理し、面接直前に必要な情報を企業ページから確認できます。

## 主な機能

- 企業と複数応募案件の管理
- 業界・職種・志望度・選考状況の管理
- 説明会、ES、適性検査、面接、内定までの選考フロー管理
- 日別詳細、仮予定、空き日時検索に対応したカレンダー
- 自己PR、ガクチカ、志望動機、逆質問などの面接用カンペ
- Google Meetなどとの画面分割を想定した面接モード
- 面接中メモの自動保存と面接ログ保存
- 企業に紐付くメモ・資料・外部共有リンクの管理
- PC・スマートフォン対応

## 動作要件

- PHP 8.1以上
- MySQL 8.0以上
- PHP拡張: `pdo_mysql`, `mbstring`, `session`
- MySQL文字コード: `utf8mb4`
- タイムゾーン: `Asia/Tokyo`

DB構造: [database/ER_DIAGRAM.md](database/ER_DIAGRAM.md)

## セットアップ

### 1. データベースを作成する

空のMySQLデータベースを作成し、マイグレーションを実行します。

```powershell
New-Item -ItemType Directory -Force Carrer_site_private
Copy-Item config.example.php Carrer_site_private\config.php
php database\migrate.php
```

PHPコマンドを利用できない環境では、`database/install_mysql.sql`の先頭にある`USE`文を作成したDB名へ変更し、phpMyAdminからインポートしてください。

詳細: [database/MYSQL_IMPORT.md](database/MYSQL_IMPORT.md)

### 2. 接続情報を設定する

`config.example.php`を参考に、Git管理対象外の`Carrer_site_private/config.php`を作成します。

```php
<?php
return [
  'db_host' => '127.0.0.1',
  'db_port' => '3306',
  'db_name' => 'career_ops',
  'db_user' => 'career_ops',
  'db_password' => 'replace_with_a_strong_password',
  'db_charset' => 'utf8mb4',
  'session_name' => 'career_ops_sid',
  'upload_dir' => __DIR__ . '/uploads',
];
```

接続情報は環境変数`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`でも指定できます。

### 3. ローカルで起動する

```powershell
php -S 127.0.0.1:8765 -t public
```

- アプリ: `http://127.0.0.1:8765/index.php`
- 使い方ページ: `http://127.0.0.1:8765/guide.php`

初回利用時は登録画面からユーザーを作成してください。

## 本番配置

推奨構成:

```text
app-root/
├─ Carrer_site_private/  # Web公開領域外
├─ database/
├─ src/
├─ views/
└─ public/               # ドキュメントルート
```

レンタルサーバーで公開ディレクトリ名が`public_html`の場合は、ローカルの`public`配下を`public_html`へ配置し、`src`、`views`、`database`、`Carrer_site_private`はその一階層上へ配置してください。

`Carrer_site_private`にはDB接続情報とアップロードファイルが含まれるため、Web公開領域へ配置しないでください。

## ファイル管理方針

- アプリ内へ保存できる実ファイルはPDF、Word、テキストのみ
- 1ファイル最大10MB
- 実ファイルは`Carrer_site_private/uploads`へ保存
- 画像、動画、Excel、PowerPoint、ZIP、ポートフォリオ等はHTTPS共有URLとして登録
- Google Drive API連携は行わず、共有リンクのみ保持

## SQLiteからの移行

SQLiteバックアップからMySQLへ移行する場合:

```powershell
php database/migrate_sqlite.php Carrer_site_private/app.sqlite
php database/verify_migration.php Carrer_site_private/app.sqlite
```

移行後のSQLiteファイルは読み取り専用バックアップとして保持してください。

## テスト

```powershell
php tests/static_smoke.php
```

ガイド用スクリーンショットを更新する場合は、Playwrightを用意し、専用テストユーザーの認証情報を環境変数で指定します。

```powershell
$env:DEMO_EMAIL='demo@example.com'
$env:DEMO_PASSWORD='replace_with_demo_password'
node tests/capture_demo.mjs
```

全PHPファイルの構文確認:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## セキュリティ

- `Carrer_site_private/`、`.env`、DBバックアップをGitへ追加しないでください。
- 本番環境ではHTTPSを使用してください。
- 公開前に強力なDBパスワードへ変更してください。
- `public/healthcheck.php`と`public/dashboardcheck.php`は一時診断用です。使用後は公開サーバーから削除してください。
- 外部共有リンクの閲覧権限は、Google Drive等の共有元サービス側で管理してください。

## ライセンス

ライセンスは設定されていません。再利用・再配布する場合は、リポジトリ所有者へ確認してください。
