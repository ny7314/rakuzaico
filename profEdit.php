<?php

require('function.php');

debug('----------');
debug('プロフィール編集ページ');
debug('----------');
debugLogStart();

// ログイン認証
require('auth.php');

// DBからユーザーデータを取得
$dbFormData = getUser($_SESSION['user_id']);

debug('取得したユーザー情報：' . print_r($dbFormData, true));

// post送信された場合
if (!empty($_POST)) {
  debug('POST送信があります。');
  debug('POST情報：' . print_r($_POST, true));
  debug('FILE情報：' . print_r($_FILES, true));

  // 変数にユーザー情報を格納
  $user_name = $_POST['user_name'];
  $tel = $_POST['tel'];
  $zip = (!empty($_POST['zip'])) ? $_POST['zip'] : 0;
  $addr = $_POST['addr'];
  $email = $_POST['email'];
  $pic = (!empty($_FILES['pic']['name'])) ? uploadImg($_FILES['pic'], 'pic') : '';
  // 既にDBに画像がある場合
  $pic = (empty($pic) && !empty($dbFormData['pic'])) ? $dbFormData['pic'] : $pic;

  if ($dbFormData['user_name'] !== $user_name) {
    // 名前の最大文字数チェック
    validMaxLen($user_name, 'user_name');
  }
  if ($dbFormData['tel'] !== $tel) {
    // tel形式チェック
    validTel($tel, 'tel');
  }
  if ($dbFormData['addr'] !== $addr) {
    // 住所の最大文字数チェック
    validMaxLen($addr, 'addr');
  }
  if ((int)$dbFormData['zip'] !== $zip) {
    // 郵便番号形式チェック
    validZip($zip, 'zip');
  }

  if ($dbFormData['email'] !== $email) {
    // emailの最大文字数チェック
    validMaxLen($email, 'email');
    if (empty($err_msg['email'])) {
      // emailの重複チェック
      validEmailDup($email);
    }
    // emailの形式チェック
    validEmail($email, 'email');
    validRequired($email, 'email');
  }

  if (empty($err_msg)) {
    debug('バリデーションOKです。');

    // 例外処理
    try {
      // DBへ接続
      $dbh = dbConnect();

      $sql = 'UPDATE users SET user_name = :u_name, tel = :tel, zip = :zip, addr = :addr, email = :email, pic = :pic WHERE id = :u_id';
      $data = array(':u_name' => $user_name, ':tel' => $tel, ':zip' => $zip, ':addr' => $addr, ':email' => $email, ':pic' => $pic, ':u_id' => $dbFormData['id']);

      $stmt = queryPost($dbh, $sql, $data);

      // クエリ成功
      if ($stmt) {
        $_SESSION['msg_success'] = SUC02;
        debug('マイページへ遷移します。');
        header("Location:mypage.php");
      }
    } catch (Exception $e) {
      error_log('エラー発生：' . $e->getMessage());
      $err_msg['common'] = MSG07;
    }
  }
}
debug('プロフィール編集処理終了');
?>
<?php
$siteTitle = 'プロフィール編集';
require('head.php');
?>

<body class="page-profEdit page-2colum page-logined">

  <?php
  require('header.php');
  ?>

  <div id="contents" class="site-width">
    <h1 class="page-title">プロフィール編集</h1>

    <section id="main">
      <div class="form-container">
        <form action="" method="POST" class="form" enctype="multipart/form-data">
          <div class="area-msg">
            <?php
            if (!empty($err_msg['common'])) echo $err_msg['common'];
            ?>
          </div>
          <label class="<?php if (!empty($err_msg['user_name'])) echo 'err'; ?>">
            お名前
            <input type="text" name="user_name" value="<?php echo getFormData('user_name'); ?>">
          </label>
          <div class="area-msg">
            <?php
            if (!empty($err_msg['user_name'])) echo $err_msg['user_name'];
            ?>
          </div>
          <label class="<?php if (!empty($err_msg['tel'])) echo 'err'; ?>" for="">
            TEL<span style="font-size:12px;margin-left:5px;">*ハイフン無しで入力してください</span>
            <input type="text" name="tel" value="<?php echo getFormData('tel'); ?>">
          </label>
          <div class="area-msg">
            <?php
            if (!empty($err_msg['tel'])) echo $err_msg['tel']; ?>
          </div>
          <label class="<?php if (!empty($err_msg['zip'])) echo 'err'; ?>">
            郵便番号<span style="font-size:12px;margin-left:5px;">*ハイフン無しで入力してください</span>
            <input type="text" name="zip" value="<?php if (!empty(getFormData('zip'))) echo getFormData('zip'); ?>">
          </label>
          <div class="area-msg">
            <?php if (!empty($err_msg['zip'])) echo $err_msg['zip']; ?>
          </div>
          <label class="<?php if (!empty($err_msg['addr'])) echo 'err'; ?>">
            住所
            <input type="text" name="addr" value="<?php echo getFormData('addr'); ?>">
          </label>
          <div class="area-msg">
            <?php
            if (!empty($err_msg['addr'])) echo $err_msg['addr']; ?>
          </div>
          <label class="<?php if (!empty($err_msg['email'])) echo 'err'; ?>">
            Email
            <input type="email" name="email" value="<?php echo getFormData('email'); ?>">
          </label>
          <div class="area-msg">
            <?php
            if (!empty($err_msg['email'])) echo $err_msg['email']; ?>
          </div>
          プロフィール画像
          <label class="area-drop <?php if (!empty($err_msg['pic'])) echo 'err'; ?>" style="height:370px;line-height:370px;">
            <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
            <input type="file" name="pic" class="input-file" style="height:370px;">
            <img src="<?php echo getFormData('pic'); ?>" alt="" class="prev-img" style="<?php if (empty(getFormData('pic'))) echo 'display:none' ?>">
            ドラッグ＆ドロップ
          </label>
          <div class="area-msg">
            <?php
            if (!empty($err_msg['pic'])) echo $err_msg['pic'];
            ?>
          </div>
          <div class="btn-container">
            <input type="submit" class="btn btn-mid" value="変更する">
          </div>
        </form>
      </div>
    </section>
    <?php
    require('sidebar_mypage.php');
    ?>
  </div>
  <?php
  require('footer.php');
  ?>