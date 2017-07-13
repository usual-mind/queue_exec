<?php
/**
 * Created by PhpStorm.
 * User: sunchuanliang
 * Date: 2017/7/12
 * Time: 07:18
 */

include "../include/db.class.php";

$db = new DB();

// 1、先把要处理的记录更新未等待处理：将记录锁定，以防其他程序操作
$waiting = array('status' => 0,);
$lock = array('status' => 2,);
$res_lock = $db->update("order_queue", $lock, $waiting, 2);

// 2、选择出刚刚更新的数据，然后进行配送系统的处理
if ($res_lock) {
//    选择出要处理的订单内容
    $sql = "select * from order_queue where status = 0 limit 2";
    $result = $db->search($sql);

//    然后由配货系统进行订单处理
    //......


// 3、把这些处理过的程序更新为已完成
    $success = array(
        'status' => 1,
        'updated_at' => date('Y-m-d H:i:s', time()),
        );
    $res_last = $db->update("order_queue", $success, $lock);
    if($res_last){
        echo "Success:".$res_last;
    } else {
        echo "Fail:".$res_last;
    }
}else {
    echo "ALL Finished";
}