<?php
session_start();
require('dbconnect.php');


if(isset($_SESSION['id'])) {
    $rt_btns = $db->prepare('SELECT COUNT(rt_post_id) AS rt_cnt FROM (SELECT rt_post_id FROM retweet WHERE pressed_member_id=? AND rt_post_id=?) p');
    $rt_btns->bindParam(1, $_SESSION['id'], PDO::PARAM_INT);
    $rt_btns->bindParam(2, $_REQUEST['id'], PDO::PARAM_INT);
    $rt_btns->execute();

    $rt_btn = $rt_btns->fetch();
}

if($rt_btn['rt_cnt'] == 1){
    $rt_dlt = $db->prepare('DELETE FROM retweet WHERE rt_post_id=? AND pressed_member_id=?');
    $rt_dlt->bindParam(1, $_REQUEST['id'], PDO::PARAM_INT);
    $rt_dlt->bindParam(2, $_SESSION['id'], PDO::PARAM_INT);
    $rt_dlt->execute();
}

header('Location: index.php');
exit();

?>