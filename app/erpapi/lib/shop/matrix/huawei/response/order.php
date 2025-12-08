<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 华为商城平台对接
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class erpapi_shop_matrix_huawei_response_order extends erpapi_shop_response_order
{
    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');
        
        if (($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) ||($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead')) {
            //如果没有退款申请单，以前端为主
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',array('order_id'=>$this->_tgOrder['order_id'], 'status|noequal'=>'3'));
            if (!$refundApply) {
                $components[] = 'master';
            }
        }
        
        //更新收货地址
        $rs = app::get('ome')->model('order_extend')->getList('extend_status', array('order_id'=>$this->_tgOrder['order_id']));
        if ($rs[0]['extend_status'] != 'consignee_modified') {
            $components[] = 'consignee';
        }
        
        return $components;
    }
}
