<?php
/**
 * Created by PhpStorm.
 * User: sunchuanliang
 * Date: 2017/7/10
 * Time: 22:55
 */
class DB {
    private $link_id;
    private $handle;    //日志文件句柄
    private $is_log;
    private $time;

    public function __construct() {
        header("Content-type: text/html; charset=utf-8");
        $this->time = $this->microtime_float();
        require_once '../configs/config.db.class.php';

        $this->connect($db_config["hostname"], $db_config["username"], $db_config["password"], $db_config["database"], $db_config["pconnect"]);

        $this->is_log = $db_config["log"];
        if($this->is_log){
            $this->handle = fopen($db_config["logfilepath"] . "dblog.txt", "a+");
        }
    }

    /**
     * 连接数据库
     */
    public function connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect = 0, $charset ='utf8') {
        if($pconnect == 0){
            $this->link_id = mysqli_connect($dbhost, $dbuser, $dbpw);

            if(!$this->link_id){
                $this->halt("数据库连接失败");
            }
        }else{
            $this->link_id = mysqli_pconnect($dbhost, $dbuser, $dbpw);
            if(!$this->link_id){
                $this->halt("数据库长连接失败");
            }
        }
        if(!mysqli_select_db($this->link_id, $dbname)){
            $this->halt("数据库选择失败");
        }
        mysqli_query("set names " . $charset);
    }

    /**
     * 查询
     * Enter description here ...
     * @param unknown_type $sql
     */
    public function query($sql){
        $this->write_log("查询 " . $sql);
        $query = mysqli_query($this->link_id, $sql);
        if(!$query){
            $this->halt("查询失败 " . $sql);
        }
        return $query;
    }

    /**
     * 获取一条记录（MYSQLI_ASSOC，MYSQLI_NUM，MYSQLI_BOTH）
     * Enter description here ...
     * @param $sql
     */
    public function get_one($sql, $result_type = MYSQLI_ASSOC){
        $query = $this->query($sql);
        $rt = mysqli_fetch_array($query, $result_type);
        $this->write_log("获取一条记录 " . $sql);
        return $rt;
    }

    /**
     * 获取全部记录
     * Enter description here ...
     * @param unknown_type $sql
     * @param unknown_type $result_type
     */
    public function get_all($sql, $is_page = false, $page_num = 10, $result_type = MYSQLI_ASSOC){
        if(!$is_page){
            $query = $this->query($sql);
            $i = 0;
            $rt = array();
            while ($row = mysqli_fetch_array($query, $result_type))
            {
                $rt[$i++] = $row;
            }
            $this->write_log("获取全部记录（无翻页）" . $sql);
        }else{
            $rt = $this->page($sql, $page_num, $result_type);
        }


        return $rt;
    }

    /**
     * 获取全部数据（带翻页）
     * Enter description here ...
     * @param $sql            sql语句
     * @param $page_num        每页显示记录条数
     */
    public function page($sql, $page_num, $result_type){
        session_start();
        $query = $this->query($sql);
        $all_num = mysqli_num_rows($query);                    //总条数
        $page_all_num = ceil($all_num / $page_num);            //总页数
        $page = empty($_GET['page']) ? 1 : $_GET['page'];    //当前页数
        $page = (int)$page;                                    //安全强制转换
        $limit_str = ($page - 1) * $page_num;                //记录起始数

        $sql .= " limit $limit_str, $page_num";
        $query = $this->query($sql);
        $i = 0;
        $rt = array();
        while ($row = mysqli_fetch_array($query, $result_type))
        {
            $rt[$i++] = $row;
        }
        $this->write_log("获取翻页记录（带翻页）" . $sql);
        $_SESSION["page_all_num"] = $page_all_num;
        $_SESSION["next"] = $page >= $page_all_num ? $page_all_num : $page + 1;
        $_SESSION["pre"] = $page <= 1 ? 1 : $page - 1;
        return $rt;
    }

    /**
     * 查询满足条件的记录
     * @param 表名 string $table
     * @param 条件 array $where
     * @param 字段 string/array $fields
     * @param 排序方式 string $order
     * @param 步长 int $skip
     * @param 条数 int $limit
     */
//    public function search($table, $where, $fields = '*', $order = '', $skip = 0, $limit = 1000) {
//        $field = "";
//        $condition =  "";
//
//        if (is_array($where)){
//            foreach ($where as $key => $value) {
//                if(is_numeric($value)) {
//                    $condition = $key.'='.$value;
//                } else {
//                    $condition = $key.'=\''.$value/'\'';
//                }
//            }
//        } else {
//            $condition = $where;
//        }
//    }
    public function search($sql)
    {
        $query = $this->query($sql);
        $result = array();
        $i = 0;
        while($row = mysqli_fetch_array($query, MYSQLI_ASSOC)){
            $result[$i++] = $row;
        }
        $this->write_log("有条件的查找数据".$sql);
        return $result;
    }

    /**
     * 插入
     * Enter description here ...
     * @param 表名 string $table
     * @param 数据 array $dataArray
     */
    public function insert($table,$dataArray) {
        $field = "";
        $value = "";
        if( !is_array($dataArray) || count($dataArray) <= 0) {
            $this->halt('没有要插入的数据');
            return false;
        }
        foreach ($dataArray as $key => $val){
            $field .="$key,";
            if(is_string($val)){
                $value .="'$val',";
            } else {
                $value .="$val,";
            }
        }

        $field = substr( $field,0,-1);
        $value = substr( $value,0,-1);
        $sql = "insert into $table($field) values($value)";
        $this->write_log("插入 ".$sql);
        if(!$this->query($sql)) return false;
        return true;
    }

    /**
     * 更新
     * Enter description here ...
     * @param 表名 string $table
     * @param 数据 array $dataArray
     * @param 条件 string $condition
     */
    public function update($table, $dataArray, $condition="", $limit = 1) {

        if( !is_array($dataArray) || count($dataArray)<=0) {
            $this->halt('没有要更新的数据');
            return false;
        }
        $value = "";
        //将需要更新的数据由数据转换成字符串
        foreach($dataArray as $key => $val){
            if(is_string($val)){
                $value .="$key = '$val',";
            } else {
                $value .="$key = $val,";
            }
        }
        $cond_Value = "";
        //如果传进来的条件是数组，则需要转换成字符串
        if(is_array($condition)){
            foreach($condition as $key => $val){
                if(is_string($val)){
                    $cond_Value .="$key = '$val',";
                } else {
                    $cond_Value .="$key = $val,";
                }
            }
            $cond_Value = substr( $cond_Value,0,-1);
        }

        $value = substr( $value,0,-1);//截取最后一个 逗号
        $sql = "update $table set $value where $cond_Value limit $limit";

        $this->write_log("更新 ".$sql);
        if(!$this->query($sql)) return false;
        return true;
    }

    /**
     * 删除
     * Enter description here ...
     * @param unknown_type $table
     * @param unknown_type $condition
     */
    public function delete( $table,$condition="") {
        if( empty($condition) ) {
            $this->halt('没有设置删除的条件');
            return false;
        }
        $sql = "delete from $table where 1=1 and $condition";
        $this->write_log("删除 ".$sql);
        if(!$this->query($sql)) return false;
        return true;
    }

    /**
     * 返回结果集
     * Enter description here ...
     * @param unknown_type $query
     * @param unknown_type $result_type
     */
    public function fetch_array($query, $result_type = MYSQLI_ASSOC){
        $this->write_log("返回结果集");
        return mysqli_fetch_array($query, $result_type);
    }

    /**
     * 获取记录条数
     * Enter description here ...
     * @param unknown_type $results
     */
    public function num_rows($results) {
        if(!is_bool($results)) {
            $num = mysqli_num_rows($results);
            $this->write_log("获取的记录条数为".$num);
            return $num;
        } else {
            return 0;
        }
    }

    /**
     * 获取最后插入的id
     * Enter description here ...
     */
    public function insert_id() {
        $id = mysqli_insert_id($this->link_id);
        $this->write_log("最后插入的id为".$id);
        return $id;
    }

    /**
     * 关闭数据库连接
     * Enter description here ...
     */
    public function close() {
        $this->write_log("已关闭数据库连接");
        return @mysqli_close($this->link_id);
    }

    /**
     * 错误提示
     */
    private function halt($msg = ''){
        $msg .= "\r\n" . mysqli_error();
        $this->write_log($msg);
        die($msg);
    }

    /**
     * 写入日志文件
     */
    public function write_log($msg = ''){
        if($this->is_log){
            $text = date("Y-m-d H:i:s",time()) . " " . $msg . "\r\n";
            fwrite($this->handle, $text);
        }
    }

    /**
     * 获得毫秒数
     */
    public function microtime_float() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * 析构函数
     * Enter description here ...
     */
    public function __destruct(){
        $use_time = ($this->microtime_float()) - ($this->time);
        $this->write_log("完成整个查询任务，所用时间为 " . $use_time);
        if($this->is_log){
            fclose($this->handle);
        }
    }
}