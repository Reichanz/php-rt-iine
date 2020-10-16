<?php
session_start();
require('dbconnect.php');


if(isset($_SESSION['id'])) {
        $id = $_REQUEST['id'];

        $retweet_btns = $db->prepare('SELECT COUNT(rt_post_id) AS rt_cnt FROM (SELECT rt_post_id FROM retweet WHERE rt_post_id=? AND pressed_member_id=? GROUP BY rt_post_id) A');
        $retweet_btns->bindParam(1, $id, PDO::PARAM_INT);
        $retweet_btns->bindParam(2, $_SESSION['id'], PDO::PARAM_INT);
        $retweet_btns->execute();
        $retweet_btn = $retweet_btns->fetch();

        //rtを登録する作業
if($retweet_btn['rt_cnt'] == 0){
    $likes = $db->prepare('INSERT INTO retweet SET rt_post_id=?, pressed_member_id=?, created=NOW()');

    $likes->bindParam(1, $id, PDO::PARAM_INT);
    $likes->bindParam(2, $_SESSION['id'], PDO::PARAM_INT);
    $likes->execute();
    }


//リツイートする予定の投稿を抜き出す
$rt_posts = $db->prepare('SELECT members.picture, members.name, posts.message FROM members, posts WHERE ');

//自分の投稿に挿入する

header('Location: index.php');
exit();
}
?>