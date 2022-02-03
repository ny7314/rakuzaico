<?php
require('function.php');

debug('----------');
debug('マイページ');
debug('----------');
debugLogStart();

//ログイン認証
require('auth.php');
// 画面表示用データ取得
$u_id = $_SESSION['user_id'];

// DBから商品データを取得
$productData = getMyProducts($u_id);
// DBから連絡掲示板データを取得
$bordData = getMyMsgsAndBord($u_id);
// DBからお気に入りデータを取得
$likeData = getMyLike($u_id);


debug('取得した商品データ：'.print_r($productData,true));
debug('取得した掲示板データ：'.print_r($bordData,true));
debug('取得したお気に入りデータ：'.print_r($likeData,true));

debug('マイページ表示データ取得処理終了');

?>
<?php
$siteTitle = 'マイページ';
require('head.php');
?>

<body class="page-mypage page-2colum page-logined">

  <?php
  require('header.php');
  ?>
  <p id="js-show-msg" style="display:none;" class="msg-slide">
    <?php echo getSessionFlash('msg_success'); ?>
</p>

  <div id="contents" class="site-width">
    <h1 class="page-title">
      マイページ
    </h1>

    <section id="main" class="page-mypage">
      <section class="list panel-list">
        <h2 class="title">
          在庫登録一覧
        </h2>
        <?php
        if(!empty($productData)):
        foreach($productData as $key => $val):
        ?>
        <a href="registProduct.php<?php echo (!empty(appendGetParam())) ? appendGetParam().'&p_id='.$val['id'] : '?p_id='.$val['id']; ?>" class="panel">
          <div class="panel-head">
            <img src="<?php echo showImg(sanitize($val['pic1'])); ?>" alt="<?php echo sanitize($val['name']); ?>">
          </div>
          <div class="panel-body">
            <p class="panel-title"><?php echo sanitize($val['name']); ?><span class="price">
                ¥<?php echo sanitize(number_format($val['price'])); ?></span>
            </p>
          </div>
        </a>
      <?php
          endforeach;
        endif;
      ?>
      </section>

      <section class="list list-table">
        <h2 class="title">
          連絡掲示板一覧
        </h2>
        <table class="table">
          <thead>
            <tr>
              <th>最新送信日時</th>
              <th>取引相手</th>
              <th>メッセージ</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if(!empty($bordData)){
              foreach($bordData as $key => $val){                if(!empty($val['msg'])){
                $msg = array_shift($val['msg']);
            ?>
              <tr>
              <td><?php echo sanitize(date('Y/m/d H:i:s', strtotime($msg['send_date']))); ?></td>
              <td><?php echo sanitize($msg['user_name']); ?></td>
              <td><a href="msg.php?m_id=<?php echo sanitize($val['id']); ?>"><?php echo mb_substr(sanitize($msg['msg']),0,40); ?>...</a></td>
              </tr>
            <?php
              }else{
            ?>
              <tr>
                  <td>--</td>
                  <td>◯◯ ◯◯</td>
                  <td><a href="msg.php?m_id=<?php echo sanitize($val['id']); ?>">まだメッセージはありません</a></td>
              </tr>
            <?php
              }
              }
            }
            ?>
          </tbody>
        </table>
      </section>

      
    </section>

    <!-- サイドバー -->
    <?php
    require('sidebar_mypage.php');
    ?>
  </div>
  <?php
  require('footer.php');
  ?>
