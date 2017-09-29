<?php
include_once 'PhpLog.php';
/*
 * 定义数据库操作的函数
 * 
 * */
 class DB_Operator
{
    public $m_dbh = NULL; //静态属性,所有数据库实例共用,避免重复连接数据库
    protected $m_dbType = 'mysql';
    protected $m_pconnect = true; //是否使用长连接
    protected $m_host = NULL;
    protected $m_port = NULL;
    protected $m_user = NULL;
    protected $m_pass = NULL;
    protected $m_dbName = NULL; //数据库名
    public  $m_sql = false; //最后一条sql语句
    protected $m_where = '';
    protected $m_order = '';
    protected $m_limit = '';
    protected $m_field = '*';
    protected $m_clear = 0; //状态，0表示查询条件干净，1表示查询条件需要被重置
    protected $m_trans = 0; //事务指令数 
/**
 * 构造函数，初始化数据库字段，并且链接数据库，给数据库对象赋值
 * @param string $dbserver  数据库所在服务器ip或名称，本地为localhost
 * @param string $port     数据端口
 * @param string $dbname   要打开的库的名字
 * @param string $usrname  数据库的用户名
 * @param string $pwd      数据库的密码
 */
    function __construct($dbserver,$port,$dbname,$usrname,$pwd)
    {
        class_exists('PDO') or die("PDO: class not exists.");
        $this->m_host = $dbserver;
        $this->m_port = $port;
        $this->m_dbName = $dbname;
        $this->m_user = $usrname;    
        $this->m_pass = $pwd;
      
        if(is_null($this->m_dbh))
            $this->f_connect();
        
    }
 /**
     * 执行sql语句，自动判断进行查询或者执行操作
     * @param string $sql SQL指令
     * @return mixed
     */
    public function f_doSql($sql='') 
    {
        $queryIps = 'INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|LOAD DATA|SELECT .* INTO|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK';
        if (preg_match('/^\s*"?(' . $queryIps . ')\s+/i', $sql)) {
            return $this->f_doExec($sql);
        }
        else {
            //查询操作
            return $this->f_doQuery($sql);
        }
    }
    
    /**
     * 获取最近一次查询的sql语句
     * @return String 执行的SQL
     */
    public function f_getLastSql() 
    {
        return $this->m_sql;
    }
    
    /**
     * 插入方法
     * @param string $tbName 操作的数据表名
     * @param array $data 字段-值的一维数组
     * @return int 受影响的行数
     */
    public function f_insert($tbName,array $data)
    {
        $data = $this->f_dataFormat($tbName,$data);
        if (!$data) return 0;
        $sql = "insert into ".$tbName."(".implode(',',array_keys($data)).") values(".implode(',',array_values($data)).")";
//        printf("%s\n",$sql);
        $ret =  $this->f_doExec($sql);
       return $ret;
    }
    
    /**
     * 删除方法
     * @param string $tbName 操作的数据表名
     * @return int 受影响的行数
     * 之前需要调用f_where函数，设置删除条件，慎用
     */
    public function f_delete($tbName) 
    {
        //安全考虑,阻止全表删除
        if (!trim($this->m_where)) return false;
        $sql = "delete from ".$tbName." ".$this->m_where;
        $this->m_clear = 1;
        $this->f_clear();
        return $this->f_doExec($sql);
    }
    
    /**
     * 更新函数
     * @param string $tbName 操作的数据表名
     * @param array $data 参数数组
     * @return int 受影响的行数
     * 之前需要调用f_where函数，设置更新条件，慎用
     */
    public function f_update($tbName,array $data) 
    {
        //安全考虑,阻止全表更新
        if (!trim($this->m_where)) 
            return 0;
        $data = $this->f_dataFormat($tbName,$data);
        if (!$data) return 0;
        $valArr = '';
        foreach($data as $k=>$v)
        {
            $valArr[] = $k.'='.$v;
        }
        $valStr = implode(',', $valArr);
        $sql = "update ".trim($tbName)." set ".trim($valStr)." ".trim($this->m_where);
        $this->m_clear = 1;
        $this->f_clear();
        return $this->f_doExec($sql);
    }
    
    /**
     * 查询函数
     * @param string $tbName 操作的数据表名
     * @return array 结果集
     * 之前需要调用f_where函数，设置查询条件，可以调用f_order，设置排序条件
     */
    public function f_select($tbName='') 
    {
        $sql = "select ".trim($this->m_field)." from ".$tbName." ".trim($this->m_where)." ".trim($this->m_order)." ".trim($this->m_limit);
        $this->m_clear = 1;
        $this->f_clear();
        return $this->f_doQuery(trim($sql));
    }
    
    /**
     * @param mixed $option 
     *          1、为直接筛选条件的字符串
     *          2、组合条件的二维数组，  key为字段名，value[0]为值 value[1]为比较关系，value[2]为与其他条件的关系
     *          
     *                      
     * @return $this
     */
    public function f_where($option) 
    {
        if ($this->m_clear>0)
            $this->f_clear();
        $this->m_where = ' where ';
        $logic = 'and';
        if (is_string($option))
        {
            $this->m_where .= $option;
        }
        elseif (is_array($option)) 
        {
            foreach($option as $k=>$v) 
            {
                if (is_array($v)) 
                {
                    $relative = isset($v[1]) ? $v[1] : '=';
                    $logic    = isset($v[2]) ? $v[2] : 'and';
                    $condition = ' ('.$this->f_addChar($k).' '.$relative.' \''.$v[0].'\') ';
                }
                else {
                    $logic = 'and';
                    $condition = ' ('.$this->f_addChar($k).'=\''.$v.'\') ';
                }
                $this->m_where .= isset($mark) ? $logic.$condition : $condition;
                $mark = 1;
            }
        }
        return $this;
    }
    
    /**
     * 设置排序
     * @param mixed $option 排序条件数组 例:array('sort'=>'desc')
     * @return $this
     */
    public function f_order($option)
    {
        if ($this->m_clear>0)
            $this->f_clear();
        $this->m_order = ' order by ';
        if (is_string($option)) 
        {
            $this->m_order .= $option;
        }
        elseif (is_array($option))
        {
            $mark = 0;
            foreach($option as $k=>$v)
            {
                $order = $this->f_addChar($k).' '.$v;
                $this->m_order .= ($mark ==1 ) ? ','.$order : $order;
                $mark = 1;
            }
        }
        return $this;
    }
    
    /**
     * 设置查询行数及页数
     * @param int $page pageSize不为空时为页数，否则为行数
     * @param int $pageSize 为空则函数设定取出行数，不为空则设定取出行数及页数
     * @return $this
     */
    public function f_limit($page,$pageSize=null) 
    {
        if ($this->m_clear>0) 
            $this->f_clear();
        if ($pageSize===null) 
        {
            $this->m_limit = "limit ".$page;
        }
        else 
        {
            if(!is_int($page)  || $page < 1)
                $page = 1;
            if(!is_int($pageSize) || $pageSize <= 1) 
                $pageSize = 1;
            $pageval = intval( ($page - 1) * $pageSize);
            $this->m_limit = "limit ".$pageval.",".$pageSize;
        }
        return $this;
    }
    
    /**
     * 设置查询字段
     * @param mixed $field 字段数组
     * @return $this
     */
    public function f_field($field)
    {
        if ($this->m_clear>0) 
            $this->f_clear();
        if (is_string($field))
        {
            $field = explode(',', $field);
        }
        elseif(is_array($field))
        {
            //$nField = array_map('f_addChar', $field);
            $nField = array();
            foreach ($field as $k=>$v)
            {
                $nField[$k] = $this->f_addChar($v);
            }
            $this->m_field = implode(',', $nField);
        }
        return $this;
    }
    

    
    /**
     * 手动清理标记
     * @return $this
     */
    public function f_clearKey()
    {
        $this->f_clear();
        return $this;
    }
    
    /**
     * 启动事务
     * @return void
     */
    public function f_startTrans() 
    {
        //数据rollback 支持
        $this->m_dbh->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
        if ($this->m_trans==0) 
            $this->m_dbh->beginTransaction();
        $this->m_trans++;
        return;
    }
     
    /**
     * 用于非自动提交状态下面的查询提交
     * @return boolen
     */
    public function f_commit() 
    {
        $result = true;
        if ($this->m_trans>0) 
        {
            $result = $this->m_dbh->commit();
            $this->m_trans = 0;
        }
        $this->m_dbh->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
        return $result;
    }
    
    /**
     * 事务回滚
     * @return boolen
     */
    public function f_rollback() 
    {
        $result = true;
        if ($this->m_trans>0) 
        {
            $result = $this->m_dbh->rollback();
            $this->m_trans = 0;
        }
        return $result;
    }
    
    /**
     * 关闭连接
     * PHP 在脚本结束时会自动关闭连接。
     */
    public function f_close() 
    {
        if (!is_null($this->m_dbh)) 
            $this->m_dbh = null;
    }
    /**
     * 链接数据库
     */
    protected function f_connect()
    {
    
        $connstr = $this->m_dbType.':host='.$this->m_host.';port='.$this->m_port.';dbname='.$this->m_dbName . ';charset=utf8';
        $options = $this->m_pconnect ? array( \PDO::ATTR_PERSISTENT >= true) : array();
        try
        {
            $dbh = new \PDO($connstr,$this->m_user,$this->m_pass,$options);
            //设置如果sql语句执行错误则抛出异常，事务会自动回滚
            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            //禁用prepared statements的仿真效果(防SQL注入)
            $dbh->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        }
        catch (\PDOException $e)
        {
            die('Connection failed: ' . $e->getMessage());
        }
//         $dbh->exec('SET NAMES utf8');
        $dbh->query("SET NAMES utf8");
        $this->m_dbh = $dbh;
    }
    /**
     * 取得数据表的字段信息
     * @param string $tbName 表名
     * @return array
     */
    public function f_tbFields($tbName)
    {
        $sql = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME="'.$tbName.'" AND TABLE_SCHEMA="'.$this->m_dbName.'"';
        $stmt = $this->m_dbh->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $ret = array();
        foreach ($result as $key=>$value)
        {
            $ret[$value['COLUMN_NAME']] = 1;
        }
        return $ret;
    }
    
    public function f_dbTables($schma)
    {
        $sql = 'select table_name from information_schema.tables where table_schema="'.$schma.'" and table_type="base table"';
        $stmt = $this->m_dbh->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $ret = array();
        foreach ($result as $key=>$value)
        {
            $ret[$value['table_name']] = 1;
        }
        return $ret; 
    }
    /**
     * 字段和表名添加 `符号
     * 保证指令中使用关键字不出错 针对mysql
     * 对于包括了*（）.暂时先不做处理
     * @param string $value
     * @return string
     */
  //  select table_name from information_schema.tables where table_schema="tyl_old" and table_type="base table"
    public function f_addChar($value)
    {
        if ('*'==$value || false!==strpos($value,'(') || false!==strpos($value,'.') || false!==strpos($value,'`'))
        {
            //如果包含* 或者 使用了sql方法 则不作处理
        }
        elseif (false === strpos($value,'`') )
        {
            $value = '`'.trim($value).'`';
        }
        return $value;
    }
     
    /**
     * 过滤并格式化数据表字段
     * @param string $tbName 数据表名
     * @param array $data POST提交数据
     * @return array $newdata
     */
    protected function f_dataFormat($tbName,$data)
    {
        if (!is_array($data))
            return array();
    
        $table_column = $this->f_tbFields($tbName);
        $ret=array();
        foreach ($data as $key=>$val)
        {
            if (!is_scalar($val))
                continue; //值不是标量则跳过
            if (array_key_exists($key,$table_column))
            {
                $key = $this->f_addChar($key);
                if (is_int($val))
                {
                    $val = intval($val);
                }
                elseif (is_float($val))
                {
                    $val = floatval($val);
                }
                elseif (preg_match('/^\(\w*(\+|\-|\*|\/)?\w*\)$/i', $val))
                {
                    // 支持在字段的值里面直接使用其它字段 ,例如 (score+1) (name) 必须包含括号
                    ;//$val = $val;
                }
                elseif (is_string($val))
                {
                    $val = '"'.addslashes($val).'"';
                }
                $ret[$key] = $val;
            }
        }
        return $ret;
    }
    public function f_select_fast($table,$arr,$page,$pagesize)
    {
        $sql = 'select ';
        $i = 0;
        foreach ( $arr as $k=>$v)
        {
            if($i == 0)
                $sql .= '`' . $v.'`';
            else 
                $sql .= ',`'. $v.'`';
            $i++;
        }
        $sql .= ' from ' . $table . ' limit ' . intval(($page - 1) * $pagesize) . ',' .$pagesize;
        print "sql:".$sql."\n";
        return $this->f_doQuery($sql);
    }
    public function  f_insert_fast($table,$arr)
    {
        $sql1 = 'insert into '.$table.'(';
        $sql2 = '(';
        $i = 0;
        foreach ($arr as $k=>$v)
        {
            if($i > 0)
            {
                $sql1 .= ',';
                $sql2 .= ',';
            }
            $sql1 .= '`'.$k.'`';
            if(!isset($v) || strlen($v) == 0)
                $sql2 .= 'DEFAULT';
            else
                $sql2 .= '\''.$v.'\'';
            $i++;
        }
        $sql = $sql1 . ') values '.$sql2.')';
        return $this->m_dbh->exec($sql);
    }
    public function f_insert_fast_muti($t_name,$arr)
    {
        if(!isset($arr[0])) return false;
        $sql1 = 'insert into '.$t_name.'(';
        $sql2 = '(';
        $i = 0;
        $arr_field = array();
        $idx_field = 0;
        foreach ($arr[0] as $k=>$v)
        {
            if($i > 0)
            {
                $sql1 .= ',';
                $sql2 .= ',';
            }
            $sql1 .= '`'.$k.'`';
            $arr_field[$idx_field++] = $k;
            if(!isset($v) || strlen($v) == 0)
                $sql2 .= 'DEFAULT';
            else
                $sql2 .= '\''.$v.'\'';
            $i++;
        }
        
        $sql = $sql1 . ') values '.$sql2.')';
        for($i = 1; ;$i++)
        {
            if(!isset($arr[$i])) break;
            $sql .= ',(';
            for($j = 0; $j < $idx_field; $j++)
            {
                
                if($j > 0) $sql .= ',';
                $v = $arr[$i][$arr_field[$j]];
                if(!isset($v) || strlen($v) == 0)
                    $sql .=  'DEFAULT';
                else
                    $sql .= '\''.$v.'\'';
            }
            $sql .= ')';
        }
//         printf("%s\n",$sql);
        return $this->m_dbh->exec($sql);
        
    }
    /**
     * 执行查询 主要针对 SELECT, SHOW 等指令,数据库读指令
     * @param string $sql sql指令
     * @return mixed
     */
    public function f_doQuery($sql='')
    {
        $this->m_sql = $sql;
        $pdostmt = $this->m_dbh->prepare($this->m_sql); //prepare或者query 返回一个PDOStatement
        $pdostmt->execute();
        $result = $pdostmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }
    /**
     * 执行语句 针对 INSERT, UPDATE 以及DELETE,exec结果返回受影响的行数，数据库写指令
     * @param string $sql sql指令
     * @return integer
     */
    protected function f_doExec($sql='')
    {
        $this->m_sql = $sql;
        return $this->m_dbh->exec($this->m_sql);
    }
    /**
     * 清理标记函数
     */
    protected function f_clear() {
        $this->m_where = '';
        $this->m_order = '';
        $this->m_limit = '';
        $this->m_field = '*';
        $this->m_clear = 0;
    }
                 
    function __destruct()
    {
        
    }
    
}
