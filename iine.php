<?php
session_start();
require('dbconnect.php');



//いいねの登録するための情報を取得
if(isset($_SESSION['id'])) {
    $id = $_REQUEST['id'];

    $iine_btns = $db->prepare('SELECT COUNT(liked_post_id) AS iine_cnt FROM (SELECT liked_post_id FROM likes WHERE liked_post_id=? AND pressed_member_id=? GROUP BY liked_post_id) p');
    $iine_btns->bindParam(1, $id, PDO::PARAM_INT);
    $iine_btns->bindParam(2, $_SESSION['id'], PDO::PARAM_INT);
    $iine_btns->execute();
    $iine_btn = $iine_btns->fetch();

//いいねを登録する作業
if($iine_btn['iine_cnt'] == 0){
    $likes = $db->prepare('INSERT INTO likes SET liked_post_id=?, pressed_member_id=?, created=NOW()');

    $likes->bindParam(1, $id, PDO::PARAM_INT);
    $likes->bindParam(2, $_SESSION['id'], PDO::PARAM_INT);
    $likes->execute();
    }

    header('Location: index.php');
    exit();
}
?>