<?php
require('function.php');

debug('----------');
debug('ログインページ');
debug('----------');
debugLogStart();

// ログイン認証
require('auth.php');

if(!empty($_POST)){
  debug('POST送信があります。');

  // 変数にユーザー情報を代入
  $name = $_POST['user_name'];
  $email = $_POST['email'];
  $pass = $_POST['pass'];
  $pass_save = (!empty($_POST['pass_save'])) ? true : false;

  // 未入力チェック
  validRequired($name, 'user_name');
  validRequired($email, 'email');
  validRequired($pass, 'pass');

  // emailの形式チェック
  validEmail($email, 'email');
  validMaxLen($email, 'email');

  // パスワードの半角英数字チェック
  validHalf($pass, 'pass');
  validMaxLen($pass, 'pass');
  validMinLen($pass, 'pass');

  if(empty($err_msg)){
    debug('バリデーションOKです。');
    // 例外処理
    try {
      // DBへ接続
      $dbh = dbConnect();
      // sql作成
      $sql = 'SELECT password, id FROM users WHERE email = :email AND delete_flg = 0';
      $data = array(':email' => $email);
      // クエリ実行
      $stmt = queryPost($dbh, $sql, $data);
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      debug('クエリ結果の中身：'.print_r($result,true));
      // パスワード照合
      if(!empty($result) && password_verify($pass, array_shift($result))){
        debug('パスワードがマッチしました。');
        // ログイン有効期限設定
        $sesLimit = 60*60;
        // 最終ログイン日時を現在に設定
        $_SESSION['login_date'] = time();
        // ログイン保持にチェックがある場合
        if($pass_save){
          debug('ログイン保持にチェックがあります。');
          // ログイン有効期限を30日にセット
          $_SESSION['login_limit'] = $sesLimit * 24 * 30;
        }else{
          debug('ログイン保持にチェックはありません。');
          // ログイン有効期限を1時間にセット
          $_SESSION['login_limit'] = $sesLimit;
        }
        // ユーザーIDを格納
        $_SESSION['user_id'] = $result['id'];

        debug('セッション変数の中身：'.print_r($_SESSION,true));
        debug('マイページへ遷移します。');
        header("Location:mypage.php");
      }else{
        debug('パスワードがアンマッチです。');
        $err_msg['common'] = MSG09;
      }

    } catch(Exception $e){
      error_log('エラー発生：'.$e->getMessage());
      $err_msg['common'] = MSG07;
    }
  }
}
debug('ログイン処理終了');
?>
<?php
$siteTitle = 'ログイン';
require('head.php');
?>

  <body class="page-login page-1colum">

  <?php
  require('header.php');
  ?> 

    <div id="contents" class="site-width">
      <!-- Main -->
      <section id="main" >
       <div class="form-container">
         <form action="" method="POST"  class="form">
           <h2 class="title">ログイン</h2>
           <div class="area-msg">
             <?php if(!empty($err_msg['common'])) echo $err_msg['common']; ?>
           </div>
           <label class="<?php if(!empty($err_msg['user_name'])) echo 'err'; ?>" for="user_name">
            お名前
             <input type="text" name="user_name" id="user_name" value="<?php if(!empty($_POST['user_name'])) echo $_POST['user_name']; ?>">
           </label>
           <div class="area-msg">
             <?php if(!empty($err_msg['user_name'])) echo $err_msg['user_name']; ?>
           </div>
           <label class="<?php if(!empty($err_msg['email'])) echo 'err'; ?>" for="email">
            メールアドレス
             <input type="text" name="email" id="email" value="<?php if(!empty($_POST['email'])) echo $_POST['email']; ?>">
           </label>
           <div class="area-msg">
             <?php if(!empty($err_msg['email'])) echo $err_msg['email']; ?>
           </div>
           <label class="<?php if(!empty($err_msg['pass'])) echo 'err'; ?>" for="pass">
             パスワード
             <input type="password" name="pass" value="<?php if(!empty($_POST['pass'])) echo $_POST['pass']; ?>">
           </label>
           <div class="area-msg">
             <?php if(!empty($err_msg['pass'])) echo $err_msg['pass']; ?>
           </div>
           <label for="checkbox">
             <input type="checkbox" id="checkbox" name="pass_save">次回ログインを省略する
           </label>
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="ログイン">
            </div>
            パスワードを忘れた方は<a href="passRemindSend.php">コチラ</a>
         </form>
       </div>
      </section>
    </div>
    <!-- footer -->
    <?php
    require('footer.php');
    ?>
    
