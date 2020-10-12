<?php
session_start();
require('dbconnect.php');


if(isset($_SESSION['id'])) {
    $iine_btns = $db->prepare('SELECT COUNT(liked_post_id) AS iine_cnt FROM (SELECT liked_post_id FROM likes WHERE pressed_member_id=? AND liked_post_id=?) p');
    $iine_btns->bindParam(1, $_SESSION['id'], PDO::PARAM_INT);
    $iine_btns->bindParam(2, $_REQUEST['id'], PDO::PARAM_INT);
    $iine_btns->execute();

    $iine_btn = $iine_btns->fetch();
}

if($iine_btn['iine_cnt'] == 1){
    $like_dlt = $db->prepare('DELETE FROM likes WHERE liked_post_id=? AND pressed_member_id=?');
    $like_dlt->bindParam(1, $_REQUEST['id'], PDO::PARAM_INT);
    $like_dlt->bindParam(2, $_SESSION['id'], PDO::PARAM_INT);
    $like_dlt->execute();
}

header('Location: index.php');
exit();

?>