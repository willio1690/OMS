<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_mdl_ar_verification extends dbeav_model{
    var $defaultOrder = array('order_bn DESC ,create_time DESC');
    public $filter_use_like = true;

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real=false){
        $tableName = 'ar';
        return $real ? kernel::database()->prefix.'finance_'.$tableName : $tableName;
    }

    /**
     * 搜索Options
     * @return mixed 返回值
     */
    public function searchOptions(){
        return array();
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias=null, $baseWhere=null){
        if(isset($filter['shop_id']) && $filter['shop_id']!='0'){
            $where .= " AND channel_id = '".$filter['shop_id']."'";
            unset($filter['shop_id']);
        }
        if(isset($filter['time_from']) && $filter['time_from']!='' && isset($filter['time_to']) && $filter['time_to']!=''){
            $where .= " AND trade_time >= ".strtotime($filter['time_from'].' 00:00:00')." AND trade_time <= ".strtotime($filter['time_to'].' 23:59:59');
            unset($filter['time_from'],$filter['time_to']);
        }
        return parent::_filter($filter, $tableAlias, $baseWhere).$where;
    }

    function modifier_type($type){
        return kernel::single('finance_ar')->get_name_by_type($type);
    }

    function modifier_status($status){
        return kernel::single('finance_ar')->get_name_by_status($status);
    }

    function modifier_charge_status($charge_status){
        return kernel::single('finance_ar')->get_name_by_charge_status($charge_status);
    }

    function modifier_monthly_status($monthly_status){
        return kernel::single('finance_ar')->get_name_by_monthly_status($monthly_status);
    }


    /*
    **实收账单核销更新
    **@params $data array('0'=>array('ar_id'=>'','unconfirm_money'=>''))
    **@params $money 核销金额（整笔交易核销总金额）
    **@return true/false bool
    */

    public function do_plus_verificate($data,$money){
        $tmp = array();
        $db = kernel::database();
        foreach ($data as $key=>$value) {
          $tmp[$value['ar_id']] = $value['unconfirm_money'];
        }
        asort($tmp);
        $standard_money = abs($money);
        foreach($tmp as $p_id=>$p_money){
            if($p_money >= $standard_money){
                $update_plus = "update sdb_finance_ar set confirm_money = (confirm_money + '".$standard_money."'),unconfirm_money = (unconfirm_money -'".$standard_money."'),status = '1' where ar_id = '".$p_id."'";
                if(!$db->exec($update_plus)){
                    $rs_flag = true;
                    break;
                }
                break;
            }else{
                $update_plus = "update sdb_finance_ar set confirm_money = money,unconfirm_money =0 ,status = 2,verification_time=".time()." where ar_id = '".$p_id."'";
                if(!$db->exec($update_plus)){
                    $rs_flag = true;
                    break;
                }
                $standard_money = abs($standard_money-$p_money);
            }
        }
        if($rs_flag == true){
            return false;
        }
        return true;
    }

    /*
    **实收账单核销更新
    **@params $data array('0'=>array('ar_id'=>'','unconfirm_money'=>''))
    **@params $money 核销金额（整笔交易核销总金额）
    **@return true/false bool
    */

    public function do_minus_verificate($data,$money){
        $tmp = array();
        $db = kernel::database();
        foreach ($data as $key=>$value) {
          $tmp[$value['ar_id']] = abs($value['unconfirm_money']);
        }
        asort($tmp);

        $standard_money = abs($money);
        foreach($tmp as $p_id=>$p_money){
            if(abs($p_money) >= abs($standard_money)){
                $update_plus = "update sdb_finance_ar set confirm_money = (confirm_money - '".$standard_money."'),unconfirm_money = (unconfirm_money +'".$standard_money."'),status = '1' where ar_id = '".$p_id."'";
                if(!$db->exec($update_plus)){
                    $rs_flag = true;
                    break;
                }
                break;
            }else{
                $update_plus = "update sdb_finance_ar set confirm_money = money,unconfirm_money =0 ,status = 2,verification_time=".time()." where ar_id = '".$p_id."'";
                if(!$db->exec($update_plus)){
                    $rs_flag = true;
                    break;
                }
                $standard_money = abs($standard_money)-abs($p_money);
            }
        }
        if($rs_flag == true){
            return false;
        }
        return true;
    }
}