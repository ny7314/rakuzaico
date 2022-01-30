<?php 

require('function.php');

debug('----------');
debug('退会ページ');
debug('----------');
debugLogStart();

// ログイン認証
require('auth.php');

// post送信がある場合
if(!empty($_POST)){
  debug('POST送信があります。');
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // sql文作成
    $sql1 = 'UPDATE users SET delete_flg = 1 WHERE id = :us_id';
    $sql2 = 'UPDATE products SET delete_flg = 1 WHERE user_id = :us_id';
    $sql3 = 'UPDATE `likes` SET delete_flg = 1 WHERE user_id = :us_id';

    $data = array(':us_id' => $_SESSION['user_id']);

    $stmt1 = queryPost($dbh, $sql1, $data);
    $stmt2 = queryPost($dbh, $sql2, $data);
    $stmt3 = queryPost($dbh, $sql3, $data);

    if($stmt1){
      // セッション削除
      session_destroy();
      debug('セッション変数の中身：'.print_r($_SESSION,true));
      debug('トップページへ遷移します。');
      header('Location:index.php');
    }else{
      debug('クエリ失敗。');
      $err_msg['common'] = MSG07;
    }
  } catch (Exception $e){
    error_log('エラー発生：' . $e->getMessage());
    $err_msg['common'] = MSG07;
  }
}
debug('処理終了');
?>
<?php
$siteTitle = '退会';
require('head.php');
?>

<body class="page-withdraw page-1colum">
  <style>
    .form .btn{
      float: none;
    }
    .form{
      text-align: center;
    }
  </style>
  <?php
  require('header.php');
  ?>

  <div id="contents" class="site-width">
    <section id="main">
      <div class="form-container">
        <form action="" method="POST" class="form">
          <h2 class="title">
            退会
          </h2>
          <div class="area-msg">
            <?php
            if(!empty($err_msg['common'])) echo $err_msg['common']; ?>
          </div>
          <div class="btn-container">
            <input type="submit" class="btn btn-mid" value="退会する" name="submit">
          </div>
        </form>
      </div>
      <a href="mypage.php">
        &lt; マイページへ戻る
      </a>
    </section>
  </div>
  <!-- footer -->
  <?php
  require('footer.php');
  ?>