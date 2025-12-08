<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

//提货校验码
class wap_code{

    function __construct($app){
        $this->app = $app;
    }

    /**
     * 生成六位提货单的校验码
     * 
     * @param string $delivery_bn
     */
    function gen_code($delivery_bn){
        $dlyCodeObj = $this->app->model('delivery_code');
        $tmp_code = rand(pow(10,5), pow(10,6)-1);
        if(@$dlyCodeObj->db->exec('INSERT INTO sdb_wap_delivery_code(`delivery_bn`,`code`,`create_time`)VALUES("'.$delivery_bn.'","'.$tmp_code.'","'.time().'")')){
            return $tmp_code;
        }else{
            $this->gen_code($delivery_bn);
        }
    }

    /**
     * 删除原有未使用的校验码
     * 
     * @param string $delivery_bn
     */
    function del_code($delivery_bn){
        $dlyCodeObj = $this->app->model('delivery_code');
        $dlyCodeObj->db->exec("delete from sdb_wap_delivery_code where status = 2 and delivery_bn ='".$delivery_bn."'");
        return true;
    }

    /**
     * 清除7天前的校验码
     * 
     * @param void
     */
    function clean_code(){
        $curr_time = strtotime(date("Y-m-d")) - 7*86400;
        $dlyCodeObj = $this->app->model('delivery_code');
        $dlyCodeObj->db->exec('delete from sdb_wap_delivery_code where status = 1 and create_time < '.$curr_time);
    }

}