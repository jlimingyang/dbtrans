<?php

include_once 'DB_Operator.php';

class dbtrans
{
    private $m_dbold;
    private $m_dbnew;

    function __construct()
    {
        $this->m_dbold = new \DB_Operator("127.0.0.1", 3306, 'anhshop', 'opuser', 'anhshop@2017');
        $this->m_dbnew = new \DB_Operator("127.0.0.1", 3306, "anhshop", 'root', '123456');
    }

    /**
     * @param $t_name
     * 当老的列和新的列相同时使用
     */
    public function Autofixedcolumn($t_name)
    {
            echo $t_name . "\n\n";
            $key_array = array();
            $col_names = $this->m_dbold->f_tbFields($t_name);
            foreach ($col_names as $c_name => $c_val) {
                $key_array[$c_name] = $c_name;
            }
           return $key_array;
    }

    /**
     * @param $old_table
     * @param $new_table
     * @return array
     * 快速检查两个表是不是匹配
     */
    public function compareTable($old_table,$new_table)
    {
        $data_first = array();
        $data_second = array();
        $data = array();
        $data1 = array();
        $i = 0;
        $j = 0;
        $old_col_names = $this->m_dbold->f_tbFields($old_table);
        foreach ($old_col_names as $c_name => $c_val) {
            $data_first[$i] = $c_name;
            $i++;
        }
        $new_col_names = $this->m_dbnew->f_tbFields($new_table);
        foreach ($new_col_names as $c_name => $c_val) {
            $data_second[$j] = $c_name;
            $j++;
        }
        print "old table:\n";
        print_r($data_first);
        print "new table:\n";
        print_r($data_second);
    }

    /**
     * @param $old_table
     * @param $new_table
     * @param $key_arr
     * 快速导表
     */
    public function trans_1to1_fast($old_table, $new_table, $key_arr)
    {
        $i = 0;
        $col_old = array();
        foreach ($key_arr as $k => $v)
            $col_old[] = $k;

        $t1 = microtime(true);
        $t2 = 0;

        $pagesize = 10000;
        $msg = '';
        for ($page = 1; ; $page++) {
            try {
                $arr_new = array();
                $arr_id = 0;
                $msg = 'select from ' . $old_table . ":\n";
                $ret_old = $this->m_dbold->f_select_fast($old_table, $col_old, $page, $pagesize);
                foreach ($ret_old as $k_old => $v_old) {

                    foreach ($key_arr as $k => $v) {
                        $arr_new[$arr_id][$v] = $v_old[$k];
                    }
                    $arr_id++;
                    $i++;
                    if (($i % 10000) == 0) {
                        $t2 = microtime(true);
                        printf("time=%s,%s insert success!\n", round($t2 - $t1, 6), $i);
                        $t1 = $t2;
                    }
                }
                $this->m_dbnew->f_insert_fast_muti($new_table, $arr_new);
                unset($arr_new);
                $arr_new = null;
            } catch (\PDOException $e) {
                die($msg . $this->m_dbnew->m_sql . "\n" . $e->getMessage());
            }
            if (count($ret_old) < $pagesize)
                printf("time=%s,%s insert error!\n"+$new_table);
            break;
        }
    }

    public function trans_1to1($old_table, $new_table, $key_arr, $flag)
    {
        $i = 0;
        $col_old = array();
        foreach ($key_arr as $k => $v)
            $col_old[] = $k;

        $t1 = microtime(true);
        $t2 = 0;

        $pagesize = 100;
        $msg = '';
        for ($page = 1; ; $page++) {
            try {
                $msg = 'select from ' . $old_table . ":\n";
                $ret_old = $this->m_dbold->f_select_fast($old_table, $col_old, $page, $pagesize);
                $arr_new = array();
                foreach ($ret_old as $k_old => $v_old) {

                    foreach ($key_arr as $k => $v) {
                        $arr_new[$v] = $v_old[$k];
                    }
                    if (isset($flag))
                        $this->m_dbnew->f_insert($new_table, $arr_new);
                    else
                        $this->m_dbnew->f_insert_fast($new_table, $arr_new);


                    $i++;
                    if (($i % 100) == 0) {
                        $t2 = microtime(true);
                        printf("time=%s,%s insert success!\n", round($t2 - $t1, 6), $i);
                        $t1 = $t2;
                    }
                }
            } catch (\PDOException $e) {
                die($msg . $this->m_dbnew->m_sql . "\n" . $e->getMessage());
            }
            if (count($ret_old) < $pagesize)
                break;
        }
    }

    public function cleardb($table_name)
    {
            $sql = 'truncate table  ' . $table_name;
            printf("%s\n",$sql);
            $this->m_dbnew->m_dbh->exec($sql);
    }

    public function delForignKey()
    {
        $sql = "SET FOREIGN_KEY_CHECKS = 0";
        $this->m_dbnew->m_dbh->exec($sql);
    }

    public function addForignKey(){
        $sql = "SET FOREIGN_KEY_CHECKS = 1";
        $this->m_dbnew->m_dbh->exec($sql);
    }

}
?>