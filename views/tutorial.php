<?php require __DIR__.'/layout/start.php'; ?>
<main class="shell tutorial-shell">
  <section class="hero"><div><span class="eyebrow">QUICK START</span><h1>画面を操作しながら使ってみる</h1><p>「ここをクリック」の案内に沿って、企業登録からセッションモードまで実際に操作します。作成するチュートリアル企業と関連データは、終了時に自動削除されます。</p></div></section>
  <div class="feature-grid tutorial-steps"><?php foreach([['01','企業を追加','チュートリアル企業を登録します。'],['02','選考フローを作る','説明会や一次面接などのステップを登録します。'],['03','メモをカンペにする','メモを保存し、セッションモードで表示できるようにします。'],['04','セッションモード','確認項目を見ながらリアルタイムメモを入力します。']] as [$n,$title,$body]):?><article><strong><?=$n?></strong><h3><?=$title?></h3><p><?=$body?></p></article><?php endforeach;?></div>
  <section class="panel tutorial-actions"><form method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="start"><button class="button primary">操作チュートリアルを開始</button></form><form method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="skip"><button class="button">スキップ</button></form></section>
</main>
<?php require __DIR__.'/layout/end.php'; ?>
