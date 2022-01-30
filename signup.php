<?php
require('function.php');

//post送信されていた場合
if(!empty($_POST)){
  // 変数にユーザー情報を代入
  $name = $_POST['user_name'];
  $email = $_POST['email'];
  $pass = $_POST['pass'];
  $pass_re = $_POST['pass_re'];

  // 未入力チェック
  validRequired($name, 'user_name');
  validRequired($email, 'email');
  validRequired($pass, 'pass');
  validRequired($pass_re, 'pass_re');

  if(empty($err_msg)){
    //user_nameの最大文字数チェック
    validMaxLen($name, 'user_name');
    
    // emailの形式チェック
    validEmail($email, 'email');
    // emailの最大文字数チェック
    validMaxLen($email, 'email');
    // emailの重複チェック
    validEmailDup($email);

    // パスワードの半角英数字チェック
    validHalf($pass, 'pass');
    // パスワードの最大文字数チェック
    validMaxLen($pass, 'pass');
    // パスワードの最小文字数チェック
    validMinLen($pass, 'pass');

    // パスワード（再入力）の最大文字数チェック
    validMaxLen($pass_re, 'pass_re');
    // パスワード（再入力）の最小文字数チェック
    validMinLen($pass_re, 'pass_re');

    if(empty($err_msg)){
      // パスワードとパスワード再入力の一致をチェック
      validMatch($pass, $pass_re, 'pass_re');

      if(empty($err_msg)){
        // 例外処理
        try {
          $dbh = dbConnect();
          $sql = 'INSERT INTO users (user_name, email, password, login_time, create_date) VALUES (:user_name, :email, :pass, :login_time, :create_date)';
          $data = array(':user_name' => $name, ':email' => $email, ':pass' => password_hash($pass, PASSWORD_DEFAULT), ':login_time' => date('Y-m-d H:i:s'), ':create_date' => date('Y-m-d H:i:s'));

          $stmt = queryPost($dbh, $sql, $data);

          if($stmt){
            $sesLimit = 60*60;
            $_SESSION['login_date'] = time();
            $_SESSION['login_limit'] = $sesLimit;
            // ユーザーIDを格納
            $_SESSION['user_id'] = $dbh->lastInsertId();

            debug('セッション変数の中身：'.print_r($_SESSION,true));

            header('Location:mypage.php');
          }
        } catch (Exception $e) {
          error_log('エラー発生：'.$e->getMessage());
          $err_msg['common'] = MSG07;
        }
      }
    }
  }
}
?>
<?php
  $siteTitle = 'ユーザー登録';
  require('head.php');
?>

<body class="page-signup page-1colum">
  <!-- ヘッダー -->
  <?php
  require('header.php');
  ?>
  
  <div id="contents" class="site-width">
    <section id="main">
      <div class="form-container">
        <form action="" method="POST" class="form">
          <h2 class="title">ユーザー登録</h2>
          <div class="area-msg">
            <?php
            if(!empty($err_msg['common'])) echo $err_msg['common'];
            ?>
          </div>

          <label for="name" class="<?php if(!empty($err_msg['user_name'])) echo 'err'; ?>">
            お名前
            <input id="name" type="text" name="user_name" value="<?php if(!empty($_POST['user_name'])) echo $_POST['user_name']; ?>">
          </label>
          <div class="area-msg">
            <?php
            if(!empty($err_msg['user_name'])) echo $err_msg['user_name'];
            ?>
          </div>

          <label for="email" class="<?php if(!empty($err_msg['email'])) echo 'err'; ?>">
            Email
            <input id="email" type="email" name="email" value="<?php if(!empty($_POST['email'])) echo $_POST['email']; ?>">
          </label>
          <div class="area-msg">
            <?php
            if(!empty($err_msg['email'])) echo $err_msg['email'];
            ?>
          </div>

          <label for="pass" class="<?php if(!empty($err_msg['pass'])) echo 'err'; ?>">
            パスワード <span style="font-size:12px">*英数字6文字以上</span>
            <input id="pass" type="password" name="pass" value="<?php if(!empty($_POST['pass'])) echo $_POST['pass']; ?>">
          </label>
          <div class="area-msg">
            <?php
            if(!empty($err_msg['pass'])) echo $err_msg['pass'];
            ?>
          </div>

          <label for="pass_re" class="<?php if(!empty($err_msg['pass_re'])) echo 'err'; ?>">
            パスワード（再入力）
            <input id="pass_re" type="password" name="pass_re" value="<?php if(!empty($_POST['pass_re'])) echo $_POST['pass_re']; ?>">
          </label>
          <div class="area-msg">
            <?php
            if(!empty($err_msg['pass_re'])) echo $err_msg['pass_re'];
            ?>
          </div>
          <div class="btn-container">
            <input type="submit" class="btn btn-mid" value="登録する">
          </div>
        </form>
      </div>
    </section>
  </div>

  <!-- footer -->
  <?php
  require('footer.php');
  ?>
