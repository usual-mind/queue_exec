<?php
/**
 * Created by PhpStorm.
 * User: sunchuanliang
 * Date: 2017/7/10
 * Time: 22:55
 */

include "../include/db.class.php";


if(!empty($_GET['mobile'])){
    $order_id = rand(1000,9999);
    $insert_data = array(
        'order_id' => $order_id,
        'mobile' => (int)$_GET['mobile'],
        'created_at' => date('Y-m-d H:i:s',time()),
        'status' => 0,
    );
//    $sql = "insert into order_queue(order_id, mobile, created_at, status) values ($insert_data[order_id], "."'"."$insert_data[mobile]"."'".",$insert_data[created_at],$insert_data[status])";

    $db = new DB();
    $res = $db->insert("order_queue",$insert_data);
    if($res) {
        echo $insert_data['order_id'].'：保存成功';
    } else {
        echo '保存失败';
    }
}
