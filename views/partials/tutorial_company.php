<aside class="tutorial-coach" data-tutorial-coach data-target="company-form">
  <span class="eyebrow">STEP 1 / 4</span>
  <h2>企業を追加する</h2>
  <p>企業名に「チュートリアル企業」と入力してください。必要なら業界なども試し、最後に「登録する」をクリックします。この企業は終了時に削除されます。</p>
  <div class="tutorial-coach-actions"><button class="button primary" type="button" data-tutorial-focus>ここを入力する</button><form action="tutorial.php" method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="skip"><button class="button" type="submit">スキップ</button></form></div>
</aside>
<script src="assets/js/tutorial.js" defer></script>
