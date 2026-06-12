<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Career OS | 就活を一か所で動かす</title>
  <link rel="icon" href="favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/guide.css">
</head>
<body class="guide-page">
<?php $loggedIn=current_user_id()!==null; ?>
<header class="guide-header"><a class="brand" href="guide.php"><span class="brand-mark">C</span><span>Career OS</span></a><nav><a href="#features">機能</a><a href="#workflow">使い方</a><a href="#quick-access">保存方法</a><?php if($loggedIn):?><a class="button primary" href="index.php">アプリへ戻る</a><?php else:?><a class="button" href="login.php">ログイン</a><a class="button primary" href="register.php">利用を始める</a><?php endif;?></nav></header>
<main>
  <section class="guide-hero"><div><span class="eyebrow">CAREER OPERATING SYSTEM</span><h1>就活の情報を集める。<br>次にやることが見える。</h1><p>企業、選考、日程、面接対策、メモを一か所で管理。面接直前に必要な情報へすぐアクセスできる、個人用の就活オペレーションシステムです。</p><div class="guide-actions"><a class="button primary" href="<?=$loggedIn?'index.php':'register.php'?>"><?=$loggedIn?'アプリへ戻る':'無料で利用を始める'?></a><a class="button" href="#workflow">使い方を見る</a></div><ul class="hero-points"><li>選考状況を一覧管理</li><li>面接中のメモを自動保存</li><li>空き日時をすぐ提示</li></ul></div><figure class="hero-shot"><img src="assets/images/guide/dashboard.png" alt="Career OSのホーム画面"></figure></section>

  <section class="guide-section" id="features"><div class="guide-heading"><span class="eyebrow">ONE PLACE, EVERY OPERATION</span><h2>就活に必要な操作を、画面間で迷わずつなぐ</h2><p>応募企業ごとの情報だけでなく、今日やることや面接中の操作まで一連の流れとして管理します。</p></div><div class="feature-grid">
    <article><strong>01</strong><h3>企業・応募管理</h3><p>志望度、職種、選考状況、マイページ、企業研究を企業単位で整理します。</p></article>
    <article><strong>02</strong><h3>選考フロー</h3><p>説明会、ES、適性検査、面接、内定までをフロー形式で追跡できます。</p></article>
    <article><strong>03</strong><h3>カレンダー・日程調整</h3><p>確定予定と仮予定を管理し、前後の余裕時間を含めた空き候補を算出します。</p></article>
    <article><strong>04</strong><h3>面接モード</h3><p>カンペとリアルタイムメモだけを表示。Google Meetとの画面分割にも対応します。</p></article>
  </div></section>

  <section class="guide-section workflow" id="workflow"><div class="guide-heading"><span class="eyebrow">HOW TO USE</span><h2>基本の使い方</h2></div>
    <article class="guide-step"><div class="step-copy"><span>STEP 1</span><h3>ホームで今日の優先順位を確認</h3><p>締切、次の選考、未完了タスク、カレンダーをまとめて確認します。朝にホームを開けば、その日に進めるべき作業が分かります。</p></div><img src="assets/images/guide/dashboard.png" alt="締切や予定を確認できるホーム画面"></article>
    <article class="guide-step reverse"><div class="step-copy"><span>STEP 2</span><h3>企業ページへ情報を集約</h3><p>応募案件、選考フロー、志望動機、企業研究、逆質問、資料リンクを企業ページへ登録します。面接前の確認場所を一つにできます。</p></div><img src="assets/images/guide/company-workspace.png" alt="企業情報と選考フローを集約した画面"></article>
    <article class="guide-step"><div class="step-copy"><span>STEP 3</span><h3>面接中は必要な情報だけを表示</h3><p>自己PRや志望動機を左側で確認しながら、右側へリアルタイムメモを入力します。メモは数秒ごとに自動保存されます。</p></div><img src="assets/images/guide/interview-mode.png" alt="面接モード画面"></article>
    <article class="guide-step reverse"><div class="step-copy"><span>STEP 4</span><h3>その場で候補日程を提示</h3><p>面接中でも日程調整パネルを開き、所要時間と前後バッファを指定して空き日時を検索できます。候補は仮予定として登録できます。</p></div><img src="assets/images/guide/interview-scheduling.png" alt="面接中の日程調整画面"></article>
  </section>

  <section class="guide-section" id="quick-access"><div class="guide-heading"><span class="eyebrow">QUICK ACCESS</span><h2>ホーム画面・ブックマークへ追加する</h2><p>面接直前でもすぐ開けるように、スマートフォンのホーム画面またはPCブラウザーのブックマークへ登録してください。</p></div>
    <div class="access-grid">
      <article class="access-card"><span class="access-device">iPhone / iPad</span><h3>Safariからホーム画面へ追加</h3><ol><li>SafariでCareer OSを開く</li><li>画面下部の共有ボタンをタップ</li><li>「ホーム画面に追加」を選ぶ</li><li>名前を確認して「追加」をタップ</li></ol><p>ホーム画面のアイコンから、通常のアプリのように開けます。</p></article>
      <article class="access-card"><span class="access-device">Android</span><h3>Chromeからホーム画面へ追加</h3><ol><li>ChromeでCareer OSを開く</li><li>右上のメニュー「︙」をタップ</li><li>「ホーム画面に追加」を選ぶ</li><li>名前を確認して追加する</li></ol><p>端末やChromeのバージョンにより「アプリをインストール」と表示される場合があります。</p></article>
      <article class="access-card"><span class="access-device">Windows PC</span><h3>ブラウザーへブックマーク</h3><div class="shortcut"><kbd>Ctrl</kbd><span>+</span><kbd>D</kbd></div><ol><li>Career OSをブラウザーで開く</li><li><strong>Ctrl + D</strong>を押す</li><li>保存先を選び「完了」を押す</li></ol><p>Chrome、Edge、Firefoxで利用できます。</p></article>
      <article class="access-card"><span class="access-device">Mac</span><h3>ブラウザーへブックマーク</h3><div class="shortcut"><kbd>⌘</kbd><span>+</span><kbd>D</kbd></div><ol><li>Career OSをブラウザーで開く</li><li><strong>Command + D</strong>を押す</li><li>保存先を選んで追加する</li></ol><p>SafariやChromeのブックマークバーへ登録すると、すぐに開けます。</p></article>
    </div>
    <aside class="access-note"><strong>登録するおすすめページ</strong><span>普段の確認は「ホーム」、面接直前の利用が多い場合は「面接モード」を登録すると便利です。</span></aside>
  </section>

  <section class="guide-cta"><span class="eyebrow">START YOUR OPERATION</span><h2>就活情報を探す時間を、準備する時間へ。</h2><p>企業登録から始めて、次の面接に必要な情報を一つずつ集約してください。</p><a class="button primary" href="<?=$loggedIn?'index.php':'register.php'?>"><?=$loggedIn?'アプリへ戻る':'利用を始める'?></a></section>
</main>
<footer class="guide-footer"><span>Career OS</span><a href="<?=$loggedIn?'index.php':'login.php'?>"><?=$loggedIn?'アプリへ戻る':'ログイン'?></a></footer>
</body>
</html>
