<aside class="tutorial-coach" data-tutorial-coach data-target="session-note">
  <span class="eyebrow">FINAL STEP</span><h2>リアルタイムメモを試す</h2>
  <p>右側のメモ欄へ入力すると数秒ごとに自動保存されます。操作を確認できたら「チュートリアルを終了」を押してください。作成したチュートリアル企業と関連データは削除されます。</p>
  <div class="tutorial-coach-actions"><button class="button" type="button" data-tutorial-focus>メモを入力する</button><form method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="complete_tutorial"><button class="button primary" type="submit">チュートリアルを終了</button></form></div>
</aside>
<script src="assets/js/tutorial.js" defer></script>
