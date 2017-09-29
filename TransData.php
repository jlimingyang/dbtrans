<?php
/**
 * Created by PhpStorm.
 * User: limingyang
 * Date: 2017/9/28
 * Time: 下午2:04
 */
include_once "dbtrans.php";

class TransData
{
    private $dbt;

    function __construct()
    {
        $this->dbt = new dbtrans();
    }

    /**
     * 测旧表结构的方法
     */
    public function tableC($old_table, $new_table)
    {
        $this->dbt->compareTable($old_table, $new_table);
    }
    /******************导表程序*******************/

    /**
     * t_user_info
     */
    public function t_user_info()
    {
        $old_table = "t_user_info";
        $new_table = "t_user_info";

        $this->dbt->delForignKey();
        $this->dbt->cleardb($new_table);
        $this->dbt->addForignKey();

        $key_array = array(
            'user_id' => 'user_id',
            'user_name' => 'user_name',
            'user_realName' => 'user_realName',
            'user_image' => 'user_image',
            'login_password' => 'user_password',
            'user_mobilePhone' => 'user_mobilePhone',
            'user_status' => 'user_status',
            'user_createTime' => 'user_createTime',
            'user_updateTime' => 'user_updateTime',
            'user_grade' => 'user_grade',
            'user_amountIntegral' => 'user_amountIntegral',
            'user_integral' => 'user_integral',
            'user_isAgreement' => 'user_isAgreement',
            'user_shopping' => 'user_shopping',
            'user_accountId' => 'user_accountId',
            'user_type' => 'user_type'
        );

        $this->dbt->trans_1to1_fast($old_table, $new_table, $key_array);
        print $new_table . " success \n";

    }

    /**
     * t_user_info_extends
     */
    public function t_user_info_extends()
    {
        $old_table = "t_user_info";
        $new_table = "t_user_info_extends";

        $this->dbt->delForignKey();
        $this->dbt->cleardb($new_table);
        $this->dbt->addForignKey();


        $key_array = array(
            'user_id' => 'user_id',
            'user_sex' => 'user_sex',
            'user_age' => 'user_age',
            'user_birthday' => 'user_birthday',
            'user_email' => 'user_email',
            'user_qq' => 'user_qq',
            'user_addr' => 'user_addr'
        );

        $this->dbt->trans_1to1_fast($old_table, $new_table, $key_array);
        print $new_table . " success \n";

    }

    /**
     * t_ad_image
     */
    public function t_ad_image()
    {

        $old_table = "t_ad_image";
        $new_table = "t_ad_image";

        $this->dbt->cleardb($new_table);

        //新表和旧表完全一样的时候
        $this->dbt->trans_1to1_fast($old_table, $new_table, $this->dbt->Autofixedcolumn($old_table));
        print $new_table . " success \n";


    }

    /**
     * t_addr_list
     */
    public function t_addr_list()
    {

        $old_table = "t_addr_list";
        $new_table = "t_addr_list";

        $this->dbt->cleardb($new_table);

        //新表和旧表完全一样的时候
        $this->dbt->trans_1to1_fast($old_table, $new_table, $this->dbt->Autofixedcolumn($old_table));
        print $new_table . " success \n";
    }


}

$td = new \TransData();
$td->tableC("t_user_warn_option", "t_user_warn_option");
//$td->t_user_info();
//$td->t_user_info_extends();
//$td->t_ad_image();
//$td->t_addr_list();
