<?php
declare(strict_types=1);
$page_title = isset($page_title) ? (string)$page_title : 'Career OS';
$active_nav = isset($active_nav) ? (string)$active_nav : '';
$flashes = consume_flashes();
$links = [
  ['index.php','ホーム','dashboard'],
  ['companyFile.php','企業・応募','companies'],
  ['campe.php','面接モード','interview'],
];
?>
<header class="app-header">
  <a class="brand" href="index.php"><span class="brand-mark">C</span><span>Career OS</span></a>
  <button class="nav-toggle" type="button" data-nav-toggle>メニュー</button>
  <nav class="main-nav" data-nav>
    <?php foreach($links as [$href,$label,$key]): ?>
      <a class="nav-link <?=$active_nav===$key?'active':''?>" href="<?=$href?>"><?=h($label)?></a>
    <?php endforeach; ?>
    <a class="nav-link" href="guide.php">使い方</a>
    <a class="nav-link" href="logout.php">ログアウト</a>
  </nav>
</header>
<?php foreach($flashes as $flash): ?>
  <div class="toast <?=h($flash['type'])?>"><?=h($flash['message'])?></div>
<?php endforeach; ?>
