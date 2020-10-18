<?php

session_start();
require('dbconnect.php');

if(isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()){
  //ログインしている
  $_SESSION['time'] = time();

  $members = $db->prepare('SELECT * FROM members WHERE id=?');
  $members->execute(array($_SESSION['id']));
  $member = $members->fetch();
} else {
  //ログインしていない
  header('Location: login.php');
  exit();
}

//投稿を記録する
if(!empty($_POST)) {
  if($_POST['message'] != '') {
    if(!isset($_REQUEST['res'])){
      $_POST['reply_post_id'] = 0;
    }
    $message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_post_id=?,created=NOW()');
    $message->execute(array(
      $member['id'],
      $_POST['message'],
      $_POST['reply_post_id']
    ));

    header('Location: index.php');
    exit();
  }
}

//ページネーション
$page = $_REQUEST['page'];
if($page == '') {
  $page = 1;
}
$page = max($page, 1);

$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page - 1) * 5;

//投稿を取得する
//postsテーブルから投稿を抽出する際、postsテーブルのidにいいねされた件数を結合しておく
$posts = $db->prepare('SELECT members.name, members.picture, b.* FROM members,
                      (SELECT posts.*, iine_cnt FROM posts LEFT JOIN
                      (SELECT liked_post_id, COUNT(liked_post_id) AS iine_cnt
                      FROM likes GROUP BY liked_post_id) AS a
                      ON posts.id=a.liked_post_id) AS b
                      WHERE members.id=b.member_id
                      ORDER BY b.created DESC LIMIT ?, 5;');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();


$posts = $db->prepare('SELECT members.name, members.picture, b.* FROM members,
                      (SELECT posts.*, rt_cnt FROM posts LEFT JOIN
                      (SELECT rt_post_id, COUNT(rt_post_id) AS rt_cnt
                        FROM retweet GROUP BY rt_post_id) AS a
                        ON posts.id=a.rt_post_id) AS b
                        WHERE members.id=b.member_id
                        ORDER BY b.created DESC LIMIT ?, 5;');
/*
$posts = $db->prepare('SELECT members.name, b.* FROM members,
                    (SELECT posts.*, rt_cnt FROM posts LEFT JOIN
                    (SELECT rt_post_id, COUNT(rt_post_id) AS rt_cnt
                      FROM retweet GROUP BY rt_post_id) AS a
                      ON posts.id=a.rt_post_id) AS b
                      WHERE members.id=b.member_id
                      ORDER BY b.created DESC LIMIT ?, 5;');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();
//$rts_t = array();
//foreach ($rts as $po) {
//  $rts_t[] = $post;
//}
/*
$rts = $db->prepare('SELECT members.name, members.picture, b.* FROM members,
                      (SELECT posts.*, rt_cnt FROM posts LEFT JOIN
                      (SELECT rt_post_id, COUNT(rt_post_id) AS rt_cnt
                      FROM retweet GROUP BY rt_post_id) AS a
                      ON posts.id=a.rt_post_id) AS b
                      WHERE members.id=b.member_id
                      ORDER BY b.created DESC LIMIT ?, 5;');
$rts->bindParam(1, $start, PDO::PARAM_INT);
$rts->execute();
*/

//$posts = $db->query('SELECT posts.*, retweet.rt_post_id, COUNT(retweet.rt_post_id) AS rt_cnt FROM posts, retweet WHERE posts.id=retweet.rt_post_id GROUP BY rt_post_id ORDER BY posts.created');;


//返信の場合
if(isset($_REQUEST['res'])) {
  //返信の処理
  $response = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=?');
  $response->execute(array($_REQUEST['res']));

  $table = $response->fetch();
  $message = '@' . $table['name'] . ' ' . $table['message'];
}


//ログインしている人がいいねした投稿を取得する
$iine_totals = $db->prepare('SELECT liked_post_id FROM likes WHERE pressed_member_id=?');
$iine_totals->bindParam(1, $_SESSION['id'], PDO::PARAM_INT);
$iine_totals->execute();
$iine_total = array();
foreach ($iine_totals as $iine_ttl) {
  $iine_total[] = $iine_ttl;
}

$rt_totals = $db->prepare('SELECT rt_post_id FROM retweet WHERE pressed_member_id=?');
$rt_totals->bindParam(1, $_SESSION['id'], PDO::PARAM_INT);
$rt_totals->execute();
$rt_total = array();
foreach ($rt_totals as $rt_ttl) {
  $rt_total[] = $rt_ttl;
}


//htmlspecialcharsのショートカット
function h($value) {
  return htmlspecialchars($value, ENT_QUOTES);
}

//本文内のURLにリンクを設定します
function makeLink ($value) {
  return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)",
  '<a href="\1\2">\1\2</a>' , $value);
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="style.css" />
</head>

<body>
<div id="wrap">
  <div id="head">
    <h1>ひとこと掲示板</h1>
  </div>
  <div id="content">
  	<div style="text-align: right"><a href="logout.php">ログアウト</a></div>
    <form action="" method="post">
      <dl>
        <dt><?php echo h($member['name']); ?>さん、メッセージをどうぞ</dt>
        <dd>
          <textarea name="message" cols="50" rows="5"><?php echo h($message); ?></textarea>
          <input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>" />
        </dd>
      </dl>
      <div>
        <p>
          <input type="submit" value="投稿する" />
        </p>
      </div>
    </form>

    <?php
    foreach($posts as $post):
    ?>
    
    <div class="msg">
    <img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>" class="main_picture" />

    <p><?php echo makeLink(h($post['message'])); ?><span class="name">（<?php echo h($post['name']); ?>）</span>[<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]</p>

    <div style="display: flex">
    <?php
    $like_number = 0;
    for($i=0; $i<count($iine_total); $i++) {
        if ($iine_total[$i]['liked_post_id'] == $post['id']) {
            $like_number = $post['id'];
            break;
        }
    }
    ?>


    <?php if($like_number > 0){ ?>
    <a class="iine_icon" href="iine_delete.php?id=<?php echo h($post['id']); ?>">❤️</a><?php echo h($post['iine_cnt']); ?>
       <?php } else { ?>
    <a class="iine_icon" href="iine.php?id=<?php echo h($post['id']); ?>">♡</a><?php echo h($post['iine_cnt']); ?>
    <?php }?>


    <?php
    $rt_number = 0;
    for($i=0; $i<count($rt_total); $i++) {
        if ($rt_total[$i]['rt_post_id'] == $post['id']) {
            $rt_number = $post['id'];
            break;
        }
    }
    ?>

    <?php if($rt_number > 0){ ?>
    <a href="rt_delete.php?id=<?php echo h($post['id']); ?>" style="padding-left: 5px;">
    <img src="image/retweet.png" width="15" height="15" alt="rt_button" />
    </a>
    <span><?php echo h($post['rt_cnt']); ?></span>
    <?php } else { ?>
    <a href="rt.php?id=<?php echo h($post['id']); ?>" style="padding-left: 5px;">
    <img src="image/non_retweet.png" width="15" height="15" alt="rt_button" />
    </a>
    <span><?php echo h($post['rt_cnt']); ?></span>
    <?php } ?>



    <p class="day"><a href="view.php?id=<?php print(h($post['id']));?>"><?php echo h($post['created']); ?></a>

    <?php
    if($post['reply_post_id'] > 0):
    ?>
    <a href="view.php?id=<?php print(h($post['reply_post_id'])); ?>">返信元のメッセージ</a>
    <?php endif; ?>


    <?php
    if($_SESSION['id'] == $post['member_id']):
    ?>
[<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color: #F33;">削除</a>]
    <?php endif; ?>
    </p>
    </div>
    </div>
<?php
endforeach;
?>

<ul class="paging">
<?php if($page > 1): ?>
<li><a href="index.php?page=<?php print($page-1); ?>">

前のページへ</a></li>
<?php else: ?>
<li>前のページへ</li>
<?php endif; ?>

<?php if($page < $maxPage): ?>
<li><a href="index.php?page=<?php print($page+1); ?>">
次のページへ</a></li>
<?php else :?>
<li>次のページへ</li>
<?php endif; ?>
</ul>
  </div>
</div>
</body>
</html>