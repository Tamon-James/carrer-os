<?php
declare(strict_types=1);
$page_title = isset($page_title) ? (string)$page_title : 'Career OS';
$active_nav = isset($active_nav) ? (string)$active_nav : '';
$body_class = isset($body_class) ? (string)$body_class : '';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?=h($page_title)?> | Career OS</title>
  <link rel="icon" href="favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/layout.css">
</head>
<body class="<?=h($body_class)?>">
<?php require public_root().'/partials/header.php'; ?>
