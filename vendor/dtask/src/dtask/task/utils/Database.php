<?php
    namespace dtask\task\utils;
    /*
     *	mysql数据库 操作基类
     *	@package	db
     *	@author		yytcpt(无影) edited by lwq 2009-4-24
    */
    define('CLIENT_MULTI_RESULTS',  131072);
    define('CONN_MYSQL_SVR_ERR',    1);
    define('CONN_MYSQL_DB_ERR',     2);

    class Database 
    {
        var $connection_id = "";
        var $shutdown_queries = array();
        var $queries = array();
        var $query_id = "";
        var $query_count = 0;
        var $record_row = array();
        var $failed = 0;
        var $halt = "";
        var $query_log = array();
        var $hostname;
        var $database;
        var $username;
        var $password;
        var $charset;
        var $log_callback;
        var $debug;
        
        private function log(){
            
        }
        
        public function __construct($options,$log_func=null)
        {
            $this->hostname = isset($options['hostname'])?$options['hostname']:'127.0.0.1';
            if(isset($options['port'])){
                $this->hostname .= ":" . $options['port'];
            }
            $this->username = isset($options['username'])?$options['username']:'root';
            $this->password = isset($options['password'])?$options['password']:'root';
            $this->database = isset($options['database'])?$options['database']:'test';
            $this->charset = isset($options['charset'])?$options['charset']:'UTF8';
            $this->debug = true;
            if($log_func){
                    $this->log_callback = $log_func;
            }else{
                $this->log_callback = function($msg){};
            }
        }
        
        /**************************
        内部私有函数	Begin
        **************************/
        //取得结果集中行的数目，仅对 SELECT 语句有效
        function num_rows($query_id="") 
        {
            if ($query_id == "") $query_id = $this->query_id;
            return @mysql_num_rows($query_id);
        }
        
        
        //发送SQL 查询，并不获取和缓存结果的行
        function query_unbuffered($sql="")
        {
            return $this->query($sql, 'mysql_unbuffered_query');
        }
        
        //从结果集中取得一行作为关联数组
        function fetch_array($sql = "")
        {
            if ($sql == "") $sql = $this->query_id;
            $this->record_row = @mysql_fetch_array($sql, MYSQL_ASSOC);
            return $this->record_row;
        }
        
        private  function shutdown_query($query_id = "")
        {
            $this->shutdown_queries[] = $query_id;
        }
        
        //错误提示
        private function halt($the_error="", $dbName="")
        {
            
            switch( $the_error )
            {
                case CONN_MYSQL_SVR_ERR:
                    $message = "连接MySQL失败, 请确认MySQL运行正常";
                    break;
                
                case CONN_MYSQL_DB_ERR:
                    $message = "选择数据库 $dbName 失败, 请确认 $dbName 是否存在";
                    break;
                    
                default:
                    $message = $the_error."\n";
                    $message .= $this->get_errno() . "\n";
                    break;
            }
            //echo($message);
            exit;
        }
        
        function __destruct()
        {
            $this->shutdown_queries = array();
            $this->close();
        }
        
        /**
         * 生成select查询语句
         * @param [IN]tbname 表名
         * @return 返回组装好的SQL语句
         */
        private function sql_select($tbname, $where="", $limit=0, $fields="*", $orderby=null, $sort="Desc"){
            if($orderby == null)
            {
                $sql = "SELECT ".$fields." FROM `".$tbname."` ".($where?" WHERE ".$where:"").($limit ? " limit ".$limit:"");
            }
            else
            {
                $sql = "SELECT ".$fields." FROM `".$tbname."` ".($where?" WHERE ".$where:"")." ORDER BY ".$orderby." ".$sort.($limit ? " limit ".$limit:"");
            }
            return $sql;
        }
        
        /**
         * 生成select插入语句
         * @param [IN]tbname 表名
         * @return 返回组装好的SQL语句
         */
        private function sql_insert($tbname, $row){
            $sqlfield = array();
            $sqlvalue = array();
            foreach ($row as $key=>$value) {
                array_push($sqlfield,"`$key`");
                array_push($sqlvalue,is_string($value)?"'" . mysql_escape_string($value)."'":$value);
            }
            
            return "INSERT INTO `".$tbname."`(".implode(',',$sqlfield).") VALUES (".implode(',',$sqlvalue).")";
        }    
        private function sql_update($tbname, $row, $where){
            $cols = array();
            foreach ($row as $key=>$value) {
                if(is_string($value)){
                    array_push($cols,"`${key}`='${value}'");
                }else{
                    array_push($cols,"`${key}`=${value}");
                }
            }
            return "UPDATE `".$tbname."` SET ".implode(',',$cols)." WHERE ".$where;
        }    
        private function sql_delete($tbname, $where){
            return "DELETE FROM `".$tbname."` WHERE ".$where;
        }
        /**************************
        内部私有函数	End
        **************************/
        
        
        
        
        /**************************
        外部调用接口函数	Begin
        **************************/
        public function open()
        {
            if(function_exists('mysql_pconnect')){
                $this->connection_id = mysql_pconnect($this->hostname, $this->username, $this->password , false);
            }else{
                $this->connection_id = mysql_connect($this->hostname, $this->username, $this->password , false);
            }

            if (!$this->connection_id ){
                $this->db_error("mysql_pconnect " . $this->database);
                return false;
            }
            
            if (!mysql_select_db($this->database, $this->connection_id)){
                $this->db_error("mysql_select_db " . $this->database);
                return false;
            }
            
            if ($this->charset){
                @mysql_unbuffered_query("SET NAMES '".$this->charset."'");
                return false;
            }
            return true;
        }
        
        private function db_error($info){
            if (is_array($info)) 
                $info = print_r($info,true);
            
            $info = ($info?$info.'error,':'') . mysql_error() . "\n";
            $this->log($info,'database',LOG_DEBUG);
        }
        
        //取得结果集中行的数目，仅对 INSERT，UPDATE 或者 DELETE
        public function affected_rows() 
        {
            return @mysql_affected_rows($this->connection_id);//??????????
        }
        
        //从结果集中取得列信息并作为对象返回，取得所有字段
        function get_result_fields($query_id="")
        {
            if ($query_id == "") $query_id = $this->query_id;
            while ($field = mysql_fetch_field($query_id)) {
                $fields[] = $field;
            }
            return $fields;
        }
        
        //返回上一个 MySQL 操作中的错误信息的数字编码
        public function get_errno()
        {
            $this->errno = @mysql_errno($this->connection_id);
            return $this->errno;
        }
        
        //取得上一步 INSERT 操作产生的 ID
        public function insert_id()
        {
            return @mysql_insert_id($this->connection_id);
        }
        //得到查询次数
        public function query_count() 
        {
            return $this->query_count;
        }
        //释放结果内存
        public function free_result($query_id="")
        {
            if ($query_id == "") $query_id = $this->query_id;
            @mysql_free_result($query_id);
        }
        //关闭 MySQL 连接
        public function close()
        {
            if ( $this->connection_id ) 
            {
                $ret = @mysql_close( $this->connection_id );
                if( ! $ret )
                {
                    $this->halt("mysql_close: \n".mysql_error());
                }
                $this->connection_id = NULL;
            }
            //return $ret;
        }
        //列出 MySQL 数据库中的表
        public function get_table_names($database){
            $result = mysql_list_tables();
            $num_tables = @mysql_num_rows($result);
            for ($i = 0; $i < $num_tables; $i++) {
                $tables[] = mysql_tablename($result, $i);
            }
            mysql_free_result($result);
            return $tables;
        }
        
        //发送SQL 查询，并返回结果集
        function query($sql, $query_type='mysql_query')
        {
            $this->log("sql:$sql"); 
            $this->query_id = $query_type($sql,$this->connection_id);
            $this->queries[] = $sql;
            if (! $this->query_id ) {
                $this->db_error("query:$sql.");
            }
            $this->query_count++;
            return $this->query_id;
        }
        
        //新增加一条记录
        public function row_insert($tbname, $row){
            $sql = $this->sql_insert($tbname, $row);
            return $this->query_unbuffered($sql);
        }
        
        //更新指定记录
        public function row_update($tbname, $row, $where){
            $sql = $this->sql_update($tbname, $row, $where);
            return $this->query_unbuffered($sql);
        }
        
        //删除满足条件的记录	
        /**
         * 删除指定表指定条件的数据记录
         * $tbname 表名, $where 查询条件, $limit 返回记录, $fields 返回字段
         * @return 返回以字段名字别名的结果集数组row[N][fields];
         */
        public function row_delete($tbname, $where){
            $sql = $this->sql_delete($tbname, $where);
            return $this->query_unbuffered($sql);
        }
        
        /**
         * 根据条件查询，返回所有记录
         * $tbname 表名, $where 查询条件, $limit 返回记录, $fields 返回字段
         * @return 返回以字段名字别名的结果集数组row[N][fields];
         */
        public function row_select($tbname, $where="", $limit=0, $fields="*", $orderby=null, $sort="DESC"){
            $sql = $this->sql_select($tbname, $where, $limit, $fields, $orderby, $sort);
            return $this->row_query($sql);
        }
        
        /**
         * 根据条件查询，返回第一条记录
         * $tbname 表名, $where 查询条件, $limit 返回记录, $fields 返回字段
         * @return 返回以字段名字别名的数组row[fields];
         */
        public function row_select_one($tbname, $where, $fields="*", $orderby="id"){
            $sql = $this->sql_select($tbname, $where, 1, $fields, $orderby);
            return $this->row_query_one($sql);
        }
        
        /**
         * 根据SQL语句查询，返回所有记录
         * $SQL 需要查询的SQL语句
         * @return 返回以字段名字别名的结果集数组row[N][fields];
         */
        public function row_query($sql)
        {
            $rs	 = $this->query($sql);
            $rs_num = $this->num_rows($rs);
            $rows = array();
            for($i=0; $i<$rs_num; $i++){
                $rows[] = $this->fetch_array($rs);
            }
            $this->free_result($rs);
            return $rows;
        }
        
        function fetch_row($sql)
        {
            $rs	 = $this->query($sql);
            $row=mysql_fetch_row($rs);
            $this->free_result($rs);
            return $row;
        }
        
        function result($rs,$row,$field=0)
        {
            return mysql_result($rs,$row,$field);
        }	
        
        /**
         * 根据SQL语句查询，返回第一条记录
         * $sql 需要查询的SQL语句，注：这里需要自己控制返回结果为1.
         * @return 返回以字段名字别名的数组row[fields];
         */
        public function row_query_one($sql)
        {
            $rs	 = $this->query($sql);
            $row = $this->fetch_array($rs);
            $this->free_result($rs);
            return $row;
        }
        
        //计数统计
        /**
         * 返回指定WHERE条件表的记录数
         * $tbname 表名, $where 查询条件
         * @return 返回记录条数;
         */
        public function row_count($tbname, $where="",$colname = '*')
        {
            $sql = "SELECT count($colname) as row_sum FROM `".$tbname."` ".($where?" WHERE ".$where:"");
            $row = $this->row_query_one($sql);
            return $row["row_sum"];
        }
        
        /**************************
        外部调用接口函数	End
        **************************/
    }
?>