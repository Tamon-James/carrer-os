<?php
$tutorialCopy=[
  'flow'=>['STEP 2 / 4','選考フローを追加する','「ステップを追加」をクリックし、説明会・ES提出・面接などを入力して保存します。','flow-open','step-dialog'],
  'note'=>['STEP 3 / 4','メモを追加する','「メモを追加」をクリックし、企業研究や面接で確認したい内容を保存します。','note-open','note-dialog'],
  'interview'=>['STEP 4 / 4','面接モードを開く','面接直前や面接中はここをクリックします。カンペとリアルタイムメモを1画面で確認できます。','interview-open',''],
];
[$stepLabel,$coachTitle,$coachBody,$target,$dialogId]=$tutorialCopy[$tutorialStage];
?>
<aside class="tutorial-coach" data-tutorial-coach data-target="<?=h($target)?>" data-dialog="<?=h($dialogId)?>">
  <span class="eyebrow"><?=h($stepLabel)?></span><h2><?=h($coachTitle)?></h2><p><?=h($coachBody)?></p>
  <div class="tutorial-coach-actions"><button class="button primary" type="button" data-tutorial-focus>ここをクリック</button><form action="tutorial.php" method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="skip"><button class="button" type="submit">スキップ</button></form></div>
</aside>
<script src="assets/js/tutorial.js" defer></script>
