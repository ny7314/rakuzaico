<?php

require('function.php');

debug('----------');
debug('商品出品登録ページ');
debug('----------');
debugLogStart();

require('auth.php');

// GETデータを格納
$p_id = (!empty($_GET['p_id'])) ? $_GET['p_id'] : '';

// DBから商品データを取得
$dbFormData = (!empty($p_id)) ? getProduct($_SESSION['user_id'], $p_id) : '';

// 新規登録か編集か判別フラグ
$edit_flg = (empty($dbFormData)) ? false : true;

// DBからカテゴリーデータを取得
$dbCategoryData = getCategory();
debug('商品ID：' . $p_id);
debug('フォーム用DBデータ：' . print_r($dbFormData, true));
debug('カテゴリーデータ：' . print_r($dbCategoryData, true));

// パラメータチェック
if (!empty($p_id) && empty($dbFormData)) {
  debug('GETパラメータの商品IDが違います。マイページへ遷移します。');
  header('Location:mypage.php');
}

// post送信がある場合
if (!empty($_POST)) {
  debug('POST送信があります。');
  debug('POST情報：' . print_r($_POST, true));
  debug('FILE情報：' . print_r($_FILES, true));

  // 変数にユーザー情報を代入
  $name = $_POST['name'];
  $category = $_POST['category_id'];
  $price = (!empty($_POST['price'])) ? $_POST['price'] : 0;

  $comment = $_POST['comment'];
  // 画像をアップロード
  $pic1 = (!empty($_FILES['pic1']['name'])) ? uploadImg($_FILES['pic1'], 'pic1') : '';
  $pic1 = (empty($pic1) && !empty($dbFormData['pic1'])) ? $dbFormData['pic1'] : $pic1;

  $pic2 = (!empty($_FILES['pic2']['name'])) ? uploadImg($_FILES['pic2'], 'pic2') : '';
  $pic2 = (empty($pic2) && !empty($dbFormData['pic2'])) ? $dbFormData['pic2'] : $pic2;
  $pic3 = (!empty($_FILES['pic3']['name'])) ? uploadImg($_FILES['pic3'], 'pic3') : '';
  $pic3 = (empty($pic3) && !empty($dbFormData['pic3'])) ? $dbFormData['pic3'] : $pic3;

  // バイデーションチェック
  if (empty($dbFormData)) {
    // 未入力チェック
    validRequired($name, 'name');
    // 最大文字数チェック
    validMaxLen($name, 'name');
    // セレクトボックスチェック
    validSelect($category, 'category_id');
    // 最大文字数チェック
    validMaxLen($comment, 'comment', 500);
    // 未入力チェック
    validRequired($price, 'price');
    // 半角英数字チェック
    validNumber($price, 'price');
  } else {
    if ($dbFormData['name'] !== $name) {
      // 未入力チェック
      validRequired($name, 'name');
      // 最大文字数チェック
      validMaxLen($name, 'name');
    }
    if ($dbFormData['category_id'] !== $category) {
      // セレクトボックスチェック
      validSelect($category, 'category_id');
    }
    if ($dbFormData['comment'] !== $comment) {
      // 最大文字数チェック
      validMaxLen($comment, 'comment', 500);
    }
    if ($dbFormData['price'] != $price) {
      // 未入力チェック
      validRequired($price, 'price');
      // 半角数字チェック
      validNumber($price, 'price');
    }
  }
  if (empty($err_msg)) {
    debug('バリデーションOKです');
    // 例外処理
    try {
      // DBへ接続
      $dbh = dbConnect();
      // 編集はupdate、新規登録はinsert
      if ($edit_flg) {
        debug('DB更新です。');
        $sql = 'UPDATE products SET name = :name, category_id = :category, price = :price, comment = :comment, pic1 = :pic1, pic2 = :pic2, pic3 = :pic3 WHERE user_id = :u_id AND id = :p_id';
        $data = array(':name' => $name, ':category' => $category, ':price' => $price, ':comment' => $comment, ':pic1' => $pic1, ':pic2' => $pic2, ':pic3' => $pic3, ':u_id' => $_SESSION['user_id'], ':p_id' => $p_id);
      } else {
        debug('DB新規登録です。');
        $sql = 'INSERT INTO products (name, category_id, price, comment, pic1, pic2, pic3, user_id, create_date) VALUES (:name, :category, :price, :comment, :pic1, :pic2, :pic3, :u_id, :date)';
        $data = array(':name' => $name, ':category' => $category, ':price' => $price, ':comment' => $comment, ':pic1' => $pic1, ':pic2' => $pic2, ':pic3' => $pic3, ':u_id' => $_SESSION['user_id'], ':date' => date('Y-m-d H:i:s'));
      }
      debug('SQL：' . $sql);
      debug('流し込みデータ：' . print_r($data, true));
      // クエリ実行
      $stmt = queryPost($dbh, $sql, $data);

      if ($stmt) {
        $_SESSION['msg_success'] = SUC04;
        debug('マイページへ遷移します。');
        header('Location:mypage.php');
      }
    } catch (Exception $e) {
      error_log('エラー発生：' . $e->getMessage());
      $err_msg['common'] = MSG07;
    }
  }
}
debug('商品登録処理終了');
?>
<?php
$siteTitle = (!$edit_flg) ? '商品出品登録' : '商品編集';
require('head.php');
?>

<body class="page-productEdit page-2colum page-logined">

  <?php
  require('header.php');
  ?>

  <div id="contents" class="site-width">
    <h1 class="page-title"><?php echo (!$edit_flg) ? '商品を出品する' : '商品を編集する'; ?></h1>

    <section id="main">
      <div class="form-container">
        <form action="" method="POST" class="form" enctype="multipart/form-data" style="width:100%;box-sizing:border-box;">
          <div class="area-msg">
            <?php if (!empty($err_msg['common'])) echo $err_msg['common'];
            ?>
          </div>
          <label class="<?php if (!empty($err_msg['name'])) echo 'err'; ?>">
            商品名<span class="label-require">必須</span>
            <input type="text" name="name" value="<?php echo getFormData('name'); ?>">
          </label>
          <div class="area-msg">
            <?php
            if (!empty($err_msg['name'])) echo $err_msg['name']; ?>
          </div>
          <label class="<?php if (!empty($err_msg['category_id'])) echo 'err'; ?>">
            カテゴリ<span class="label-require">必須</span>
            <select name="category_id" id="">
              <option value="0" <?php if (getFormData('category_id') == 0) {
                                  echo 'selected';
                                } ?>>選択してください</option>
              <?php
              foreach ($dbCategoryData as $key => $val) {
              ?>
                <option value="<?php echo $val['id'] ?>" <?php if (getFormData('category_id') == $val['id']) {
                                                            echo 'selected';
                                                          } ?>><?php echo $val['name']; ?></option>
              <?php
              }
              ?>
            </select>
          </label>
          <div class="area-msg">
            <?php
            if (!empty($err_msg['category_id'])) echo $err_msg['category_id'];
            ?>
          </div>
          <label class="<?php if (!empty($err_msg['comment'])) echo 'err'; ?>">
            詳細
            <textarea name="comment" id="js-count" cols="30" rows="10" style="height:150px;"><?php echo getFormData('comment'); ?></textarea>
          </label>
          <p class="counter-text"><span id="js-count-view">0</span>/500文字</p>
          <div class="area-msg">
            <?php if (!empty($err_msg['comment'])) echo $err_msg['comment'];
            ?>
          </div>
          <label style="text-align:left;" class="<?php if (!empty($err_msg['price'])) echo 'err'; ?>">
            金額<span class="label-require">必須</span>
            <div class="form-group">
              <input type="text" name="price" style="width:150px;" placeholder="" value="<?php echo (!empty(getFormData('price'))) ? getFormData('price') : ''; ?>"><span class="option">円</span>
            </div>
          </label>
          <div class="area-msg">
            <?php
            if (!empty($err_msg['price'])) echo $err_msg['price'];
            ?>
          </div>
          <div style="overflow:hidden;">
            <div class="imgDrop-container">
              画像１
              <label class="area-drop <?php if (!empty($err_msg['pic1'])) echo 'err'; ?>">
                <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                <input type="file" name="pic1" class="input-file">
                <img src="<?php echo getFormData('pic1'); ?>" alt="" class="prev-img" style="<?php if (empty(getFormData('pic1'))) echo 'display:none;' ?>">
                ドラッグ＆ドロップ
              </label>
              <div class="area-msg">
                <?php if (!empty($err_msg['pic1'])) echo $err_msg['pic1'];
                ?>
              </div>
            </div>
            <div class="imgDrop-container">
              画像２
              <label class="area-drop <?php if (!empty($err_msg['pic2'])) echo 'err'; ?>">
                <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                <input type="file" name="pic2" class="input-file">
                <img src="<?php echo getFormData('pic2'); ?>" alt="" class="prev-img" style="<?php if (empty(getFormData('pic2'))) echo 'display:none;' ?>">
                ドラッグ＆ドロップ
              </label>
              <div class="area-msg">
                <?php if (!empty($err_msg['pic2'])) echo $err_msg['pic2'];
                ?>
              </div>
            </div>
            <div class="imgDrop-container">
              画像３
              <label class="area-drop <?php if (!empty($err_msg['pic3'])) echo 'err'; ?>">
                <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                <input type="file" name="pic3" class="input-file">
                <img src="<?php echo getFormData('pic3'); ?>" alt="" class="prev-img" style="<?php if (empty(getFormData('pic3'))) echo 'display:none;' ?>">
                ドラッグ＆ドロップ
              </label>
              <div class="area-msg">
                <?php if (!empty($err_msg['pic3'])) echo $err_msg['pic3'];
                ?>
              </div>
            </div>
          </div>
          <div class="btn-container">
            <input type="submit" class="btn btn-mid" value="<?php echo (!$edit_flg) ? '出品する' : '更新する'; ?>">
          </div>
        </form>
      </div>
    </section>
    <!-- サイドバー -->
    <?php
    require('sidebar_mypage.php');
    ?>
  </div>
  <!-- footer -->
  <?php
  require('footer.php');
  ?>