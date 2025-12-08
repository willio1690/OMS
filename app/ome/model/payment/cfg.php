<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_payment_cfg extends dbeav_model{
    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = array(1);
        if(isset($filter['shop_id'])){
            $paymentShopObj = $this->app->model("payment_shop");
            $payments = $paymentShopObj->getList('pay_bn', array('shop_id'=>$filter['shop_id']));
            $pay_bn = array(1);
            foreach($payments as $payment){
                $pay_bn[] = $payment['pay_bn'];
            }

            $where[] = ' pay_bn in(\''.implode('\',\'', $pay_bn).'\')';
            unset($filter['shop_id']);
        }
        return parent::_filter($filter,$tableAlias,$baseWhere)." AND ".implode(' AND ', $where);
    }

    /**
     * modifier_pay_type
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_pay_type($row){
        $tmp = ome_payment_type::pay_type_name($row);
        return $tmp;
    }
}