<?php
// ログを取る
ini_set('log_errors', 'on');
// ログの出力ファイルを指定
ini_set('error_log', 'php.log');

// デバッグ
$debug_flg = true;
function debug($str)
{
  global $debug_flg;
  if (!empty($debug_flg)) {
    error_log('デバッグ：' . $str);
  }
}

// セッション
session_save_path("/var/tmp/");
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
ini_set('session.cookie_lifetime', 60 * 60 * 24 * 30);
session_start();
session_regenerate_id();

// ログ表示関数
function debugLogStart()
{
  debug('----------');
  debug('セッションID：' . session_id());
  debug('セッション変数の中身：' . print_r($_SESSION, true));
  debug('現在日時タイムスタンプ：' . time());
  if (!empty($_SESSION['login_date']) && !empty($_SESSION['login_limit'])) {
    debug('ログイン期限日時タイムスタンプ：' . ($_SESSION['login_date'] + $_SESSION['login_limit']));
  }
}
// エラーメッセージ
define('MSG01', '入力必須です。');
define('MSG02', 'Emailの形式で入力してください');
define('MSG03', 'パスワード（再入力）が合っていません');
define('MSG04', '半角英数字のみご利用いただけます');
define('MSG05', '6文字以上で入力してください');
define('MSG06', '256文字以内で入力してください');
define('MSG07', 'エラーが発生しました。しばらく経ってからやり直してください。');
define('MSG08', 'そのEmailは既に登録されています');
define('MSG09', 'メールアドレスまたはパスワードが違います');
define('MSG10', '電話番号の形式が違います');
define('MSG11', '郵便番号の形式が違います');
define('MSG12', '半角数字のみ使用できます');
define('MSG13', '半角数字のみご利用いただけます。');
define('MSG14', '文字で入力してください');
define('MSG15', '正しくありません');
define('SUC01', '登録しました。');
define('SUC02', 'プロフィールを変更しました。');
define('SUC04', '登録しました');
define('SUC05', '購入しました。相手と連絡を取りましょう。');

// エラーメッセージ格納用
$err_msg = array();

// バリデーション
// 未入力チェック
function validRequired($str, $key)
{
  if ($str === '') {
    global $err_msg;
    $err_msg[$key] = MSG01;
  }
}
// Email形式チェック
function validEmail($str, $key)
{
  if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $str)) {
    global $err_msg;
    $err_msg[$key] = MSG02;
  }
}
// Email重複チェック
function validEmailDup($email)
{
  global $err_msg;
  // 例外処理
  try {
    $dbh = dbConnect();
    $sql = "SELECT count(*) FROM users WHERE email = :email AND delete_flg = 0";
    $data = array(':email' => $email);
    $stmt = queryPost($dbh, $sql, $data);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty(array_shift($result))) {
      $err_msg['email'] = MSG08;
    }
  } catch (Exception $e) {
    error_log('エラー発生：' . $e->getMessage());
    $err_msg['common'] = MSG07;
  }
}
// 同値チェック
function validMatch($str1, $str2, $key)
{
  if ($str1 !== $str2) {
    global $err_msg;
    $err_msg[$key] = MSG03;
  }
}
// 最小文字数チェック
function validMinLen($str, $key, $min = 6)
{
  if (mb_strlen($str) < $min) {
    global $err_msg;
    $err_msg[$key] = MSG05;
  }
}
// 最大文字数チェック
function validMaxLen($str, $key, $max = 255)
{
  if (mb_strlen($str) > $max) {
    global $err_msg;
    $err_msg[$key] = MSG06;
  }
}
// 半角チェック
function validHalf($str, $key)
{
  if (!preg_match("/^[a-zA-Z0-9]+$/", $str)) {
    global $err_msg;
    $err_msg[$key] = MSG04;
  }
}
// 電話番号形式チェック
function validTel($str, $key)
{
  if (!preg_match("/0\d{1,4}\d{1,4}\d{4}/", $str)) {
    global $err_msg;
    $err_msg[$key] = MSG10;
  }
}
// 郵便番号形式チェック
function validZip($str, $key)
{
  if (!preg_match("/^\d{7}$/", $str)) {
    global $err_msg;
    $err_msg[$key] = MSG11;
  }
}
// 半角数字チェック
function validNumber($str, $key)
{
  if (!preg_match("/^[0-9]+$/", $str)) {
    global $err_msg;
    $err_msg[$key] = MSG13;
  }
}
// 固定長チェック
function validLength($str, $key, $len = 8)
{
  if (mb_strlen($str) !== $len) {
    global $err_msg;
    $err_msg[$key] = $len . MSG14;
  }
}
// パスワードチェック
function validPass($str, $key)
{
  // 半角英数字チェック
  validHalf($str, $key);
  // 最大文字数チェック
  validMaxLen($str, $key);
  // 最小文字数チェック
  validMinLen($str, $key);
}
// selectboxチェック
function validSelect($str, $key)
{
  if (!preg_match("/^[0-9]+$/", $str)) {
    global $err_msg;
    $err_msg[$key] = MSG15;
  }
}
// エラーメッセージ表示
function getErrMsg($key)
{
  global $err_msg;
  if (!empty($err_msg[$key])) {
    return $err_msg[$key];
  }
}
// ログイン認証関数
function isLogin()
{
  if (!empty($_SESSION['login_date'])) {
    debug('ログイン済みユーザーです。');
    // 現在日時が最終ログイン日時＋有効期限を超えていた場合
    if (($_SESSION['login_date'] + $_SESSION['login_limit']) < time()) {
      debug('ログイン有効期限オーバーです。');
      // セッションを削除
      session_destroy();
      return false;
    } else {
      debug('ログイン有効期限以内です。');
      return true;
    }
  } else {
    debug('未ログインユーザーです。');
    return false;
  }
}
// DB接続関数
function dbConnect()
{
  $dsn = "mysql:dbname=rakuzaico;host=localhost;charset=utf8";
  $user = 'root';
  $password = 'root';
  $options = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
  );
  // PDOオブジェクト生成
  $dbh = new PDO($dsn, $user, $password, $options);
  return $dbh;
}
// sql実行関数
function queryPost($dbh, $sql, $data)
{
  $stmt = $dbh->prepare($sql);
  if (!$stmt->execute($data)) {
    debug('クエリに失敗しました。');
    debug('失敗したsql：' . print_r($stmt, true));
    $err_msg['common'] = MSG07;
    return false;
  }
  debug('クエリ成功');
  return $stmt;
}

// ユーザー情報取得関数
function getUser($u_id)
{
  debug('ユーザー情報を取得します。');
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // sql文作成
    $sql = 'SELECT * FROM users WHERE id = :u_id AND delete_flg = 0';
    $data = array(':u_id' => $u_id);

    $stmt = queryPost($dbh, $sql, $data);

    if ($stmt) {
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：' . $e->getMessage());
  }
}
// 商品取得関数
function getProduct($u_id, $p_id)
{
  debug('商品情報を取得します。');
  debug('ユーザーID：' . $u_id);
  debug('商品ID：' . $p_id);
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // sql文作成
    $sql = 'SELECT * FROM products WHERE user_id = :u_id AND id = :p_id AND delete_flg = 0';
    $data = array(':u_id' => $u_id, ':p_id' => $p_id);
    $stmt = queryPost($dbh, $sql, $data);

    if ($stmt) {
      // クエリ結果のデータを１レコード返却
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：' . $e->getMessage());
  }
}
// 商品一覧取得関数
function getProductList($currentMinNum = 1, $category, $sort, $span = 20)
{
  debug('商品情報を取得します。');
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    $sql = "SELECT id FROM products";
    if (!empty($category)) $sql .= ' WHERE category_id = ' . $category;
    if (!empty($sort)) {
      switch ($sort) {
        case 1:
          $sql .= ' ORDER BY price ASC';
          break;
        case 2:
          $sql .= ' ORDER BY price DESC';
          break;
      }
    }
    $data = array();
    //　クエリ実行
    $stmt = queryPost($dbh, $sql, $data);
    $rst['total'] = $stmt->rowCount();
    $rst['total_page'] = ceil($rst['total'] / $span);
    if (!$stmt) {
      return false;
    }
    // ページング用sql
    $sql = 'SELECT * FROM products';
    if (!empty($category)) $sql .= ' WHERE category_id = ' . $category;
    if (!empty($sort)) {
      switch ($sort) {
        case 1:
          $sql .= ' ORDER BY price ASC';
          break;
        case 2:
          $sql .= ' ORDER BY price DESC';
          break;
      }
    }
    $sql .= ' LIMIT ' . $span . ' OFFSET ' . $currentMinNum;
    $data = array();
    debug('SQL：' . $sql);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);
    if ($stmt) {
      // クエリ結果の全レコードを格納
      $rst['data'] = $stmt->fetchAll();
      return $rst;
    } else {
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：' . $e->getMessage());
  }
}
// 商品情報取得
function getProductOne($p_id)
{
  debug('商品情報を取得します。');
  debug('商品ID：' . $p_id);
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // SQL作成
    $sql = 'SELECT p.id, p.name, p.comment, p.price, p.pic1, p.pic2, p.pic3, p.user_id, p.create_date, p.update_date, c.name AS category FROM products AS p LEFT JOIN category AS c ON p.category_id = c.id WHERE p.id = :p_id AND p.delete_flg = 0 AND c.delete_flg = 0';
    $data = array(':p_id' => $p_id);
    $stmt = queryPost($dbh, $sql, $data);
    if ($stmt) {
      // クエリ結果のデータを１レコード返却
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：' . $e->getMessage());
  }
}
// 自分の出品情報を取得
function getMyProducts($u_id)
{
  debug('自分の商品情報を取得します。');
  debug('ユーザーID：' . $u_id);
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // sql作成
    $sql = 'SELECT * FROM products WHERE user_id = :u_id AND delete_flg = 0';
    $data = array(':u_id' => $u_id);
    $stmt = queryPost($dbh, $sql, $data);

    if ($stmt) {
      return $stmt->fetchAll();
    } else {
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：' . $e->getMessage());
  }
}
// 自分のお気に入り情報を取得
function getMyLike($u_id)
{
  debug('自分のお気に入り情報を取得します');
  debug('ユーザーID：' . $u_id);
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // SQL作成
    $sql = 'SELECT * FROM `likes` AS l LEFT JOIN products AS p ON l.product_id = p.id WHERE l.user_id = :u_id';
    $data = array(':u_id' => $u_id);
    $stmt = queryPost($dbh, $sql, $data);

    if ($stmt) {
      return $stmt->fetchAll();
    } else {
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：' . $e->getMessage());
  }
}

// メッセージボード取得関数
function getMsgsAndBord($id)
{
  debug('msg情報を取得します。');
  debug('掲示板ID：' . $id);
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // sql文作成
    $sql = 'SELECT m.id AS m_id, product_id, bord_id, send_date, to_user, from_user, sale_user, buy_user, msg, b.create_date FROM messages AS m RIGHT JOIN bords AS b ON b.id = m.bord_id WHERE b.id = :id AND b.delete_flg = 0 ORDER BY send_date ASC';
    $data = array(':id' => $id);
    $stmt = queryPost($dbh, $sql, $data);

    if ($stmt) {
      return $stmt->fetchAll();
    } else {
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：' . $e->getMessage());
  }
}
// 自分のメッセージ情報を取得
function getMyMsgsAndBord($u_id)
{
  debug('自分のmsg情報を取得します。');
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();

    // sql作成
    $sql = 'SELECT * FROM bords AS b WHERE b.sale_user = :id OR b.buy_user = :id AND b.delete_flg = 0';
    $data = array(':id' => $u_id);
    $stmt = queryPost($dbh, $sql, $data);
    $rst = $stmt->fetchAll();
    if (!empty($rst)) {
      foreach ($rst as $key => $val) {
        // sql作成
        // $sql = 'SELECT * FROM messages WHERE bord_id = :id AND delete_flg = 0 ORDER BY send_date DESC';
        $sql = 'SELECT bord_id, send_date, to_user, from_user, msg, user_name FROM messages AS m INNER JOIN bords AS b ON m.bord_id = b.id INNER JOIN users AS u ON m.to_user = u.id WHERE bord_id = :id ORDER BY send_date DESC';
        $data = array(':id' => $val['id']);
        $stmt = queryPost($dbh, $sql, $data);
        $rst[$key]['msg'] = $stmt->fetchAll();
      }
    }
    if ($stmt) {
      return $rst;
    } else {
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：' . $e->getMessage());
  }
}
// カテゴリー取得関数
function getCategory()
{
  debug('カテゴリー情報を取得します。');
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // sql作成
    $sql = 'SELECT * FROM category';
    $data = array();
    $stmt = queryPost($dbh, $sql, $data);

    if ($stmt) {
      // クエリ結果の全データを返却
      return $stmt->fetchAll();
    } else {
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：' . $e->getMessage());
  }
}
// お気に入り情報取得関数
function isLIke($u_id, $p_id)
{
  debug('お気に入り情報があるか確認します。');
  debug('ユーザーID：' . $u_id);
  debug('商品ID：' . $p_id);
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // sql作成
    $sql = 'SELECT * FROM `likes` WHERE product_id = :p_id AND user_id = :u_id';
    $data = array(':p_id' => $p_id, ':u_id' => $u_id);
    $stmt = queryPost($dbh, $sql, $data);

    if ($stmt->rowCount()) {
      debug('お気に入りです');
      return true;
    } else {
      debug('特に気に入ってません');
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：' . $e->getMessage());
  }
}
// サニタイズ関数
function sanitize($str)
{
  return htmlspecialchars($str, ENT_QUOTES);
}

// フォーム入力保持
function getFormData($str, $flg = false)
{
  if ($flg) {
    $method = $_GET;
  } else {
    $method = $_POST;
  }
  global $dbFormData;
  // ユーザーデータがある場合
  if (!empty($dbFormData)) {
    // フォームのエラーがある場合
    if (!empty($err_msg[$str])) {
      // postデータある場合
      if (isset($method[$str])) {
        return sanitize($method[$str]);
      } else {
        return sanitize($dbFormData[$str]);
      }
    } else {
      if (isset($method[$str]) && $method[$str] !== $dbFormData[$str]) {
        return sanitize($method[$str]);
      } else {
        return sanitize($dbFormData[$str]);
      }
    }
  } else {
    if (isset($method[$str])) {
      return sanitize($method[$str]);
    }
  }
}
// sessionを１回だけ取得できる
function getSessionFlash($key)
{
  if (!empty($_SESSION[$key])) {
    $data = $_SESSION[$key];
    $_SESSION[$key] = '';
    return $data;
  }
}
// 画像処理
function uploadImg($file, $key)
{
  debug('画像アップロード処理開始');
  debug('FILE情報：' . print_r($file, true));

  if (isset($file['error']) && is_int($file['error'])) {
    try {
      switch ($file['error']) {
        case UPLOAD_ERR_OK:
          break;
        case UPLOAD_ERR_NO_FILE:
          throw new RuntimeException('ファイルが選択されていません');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          throw new RuntimeException('ファイルサイズが大きすぎます。');
        default:
          throw new RuntimeException('その他のエラーが発生しました');
      }
      // mimeタイプのチェック
      $type = @exif_imagetype($file['tmp_name']);
      if (!in_array($type, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
        throw new RuntimeException('画像形式が未対応です。');
      }
      // 画像ファイル名の重複を防ぐためにハッシュ化する
      $path = 'uploads/' . sha1_file($file['tmp_name']) . image_type_to_extension($type);

      if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('ファイル保存時にエラーが発生しました');
      }
      // 保存したファイルの権限を変更する
      chmod($path, 0644);

      debug('ファイルは正常にアップロードされました');
      debug('ファイルパス：' . $path);
      return $path;
    } catch (RuntimeException $e) {
      debug($e->getMessage());
      global $err_msg;
      $err_msg[$key] = $e->getMessage();
    }
  }
}
// pagination 
function pagination(
  $currentPageNum,
  $totalPageNum,
  $link = '',
  $pageColNum = 5
) {
  // 現在のページが総ページを同じ、かつ総ページ数が表示項目数以上なら左にリンク4個出す
  if ($currentPageNum == $totalPageNum && $totalPageNum >= $pageColNum) {
    $minPageNum = $currentPageNum - 4;
    $maxPageNum = $currentPageNum;
    // 現在のページが総ページ数の1ページ前なら左に3個、右に1個出す
  } elseif ($currentPageNum == ($totalPageNum - 1) && $totalPageNum >= $pageColNum) {
    $minPageNum = $currentPageNum - 3;
    $maxPageNum = $currentPageNum + 1;
    // 現在のページが２の場合は左にリンク１、右に3個出す
  } elseif ($currentPageNum == 2 && $totalPageNum >= $pageColNum) {
    $MinPageNum = $currentPageNum - 1;
    $maxPageNum = $currentPageNum + 3;
    // 現在のページが１の場合は左に何も出さない。右に5個出す。
  } elseif ($currentPageNum == 1 && $totalPageNum >= $pageColNum) {
    $minPageNum = $currentPageNum;
    $maxPageNum = 5;
    //総ページ数が表示項目数より少ない場合は、総ページ数をループのmax,ループのminを１に設定
  } elseif ($totalPageNum < $pageColNum) {
    $minPageNum = 1;
    $maxPageNum = $totalPageNum;
    // それ以外は左に2個出す
  } else {
    $minPageNum = $currentPageNum - 2;
    $maxPageNum = $currentPageNum + 2;
  }
  echo '<div class="pagination">';
  echo '<ul class="pagination-list">';
  if ($currentPageNum != 1) {
    echo '<li class="list-item">
        <a href="?p=1' . $link . '">&lt;</a>
        </li>';
  }
  for ($i = $minPageNum; $i <= $maxPageNum; $i++) {
    echo '<li class="list-item ';
    if ($currentPageNum == $i) {
      echo 'active';
    }
    echo '"><a href="?p=' . $i . $link . '">' . $i . '</a></li>';
  }
  if ($currentPageNum != $maxPageNum) {
    echo '<li class="list-item">
        <a href="?p=' . $maxPageNum . $link . '">&gt;</a></li>';
  }
  echo '</ul>';
  echo '</div>';
}
// 画像表示用関数
function showImg($path)
{
  if (empty($path)) {
    return 'img/sample-img.png';
  } else {
    return $path;
  }
}
// GETパラメータ付与関数
function appendGetParam($arr_del_key = array())
{
  if (!empty($_GET)) {
    $str = '?';
    foreach ($_GET as $key => $val) {
      if (!in_array($key, $arr_del_key, true)) {
        $str .= $key . '=' . $val . '&';
      }
    }
    $str = mb_substr($str, 0, -1, "UTF-8");
    return $str;
  }
}
// 取引相手の情報取得関数
// function partnerData()
// {
//   // GETパラメータを取得
//   $m_id = (!empty($_GET['m_id'])) ? $_GET['m_id'] : '';
//   // DBから掲示板とメッセージデータを取得
//   $viewData = getMsgsAndBord($m_id);
//   debug('取得したDBデータ：' . print_r($viewData, true));
//   // パラメータに不正な値がはいっていないかチェック
//   if (empty($viewData)) {
//     error_log('エラー発生：指定ページに不正な値が入りました');
//     header('Location: mypage.php');
//   }
//   $productInfo = getProductOne($viewData[0]['product_id']);
//   debug('取得したDBデータ：' . print_r($productInfo, true));
//   // 商品情報が入っているかチェック
//   if (empty($productInfo)) {
//     error_log('エラー発生：商品情報が取得できませんでした。');
//     header('Location: mypage.php');
//   }
//   // viewDataから相手のユーザーIDを取り出す
//   $dealUserIds[] = $viewData[0]['sale_user'];
//   $dealUserIds[] = $viewData[0]['buy_user'];
//   if (($key = array_search($_SESSION['user_id'], $dealUserIds)) !== false) {
//     unset($dealUserIds[$key]);
//   }
//   $partnerUserId = array_shift($dealUserIds);
//   debug('取得した相手のユーザーID：' . $partnerUserId);
//   // DBから取引相手のユーザー情報を取得
//   if (isset($partnerUserId)) {
//     $partnerUserInfo = getUser($partnerUserId);
//   }
//   // 相手のユーザー情報が取れたかチェック
//   if (empty($partnerUserInfo)) {
//     error_log('エラー発生：相手のユーザー情報が取得できません');
//     header('Location: mypage.php');
//   }
//   // DBから自分のユーザー情報が取れたかチェック
//   $myUserInfo = getUser($_SESSION['user_id']);
//   debug('取得したユーザーデータ：' . print_r($partnerUserInfo, true));
//   if (empty($myUserInfo)) {
//     error_log('エラー発生：自分のユーザー情報が取得できません');
//     header('Location: mypage.php');
//   }
// }
