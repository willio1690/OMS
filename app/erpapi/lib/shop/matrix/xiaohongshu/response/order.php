<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 小红书订单 by wangjianjun 20170815
 */
class erpapi_shop_matrix_xiaohongshu_response_order extends erpapi_shop_response_order
{
    
    protected $_update_accept_dead_order = true;

    protected function get_update_components()
    {
        $components = array('markmemo','custommemo','marktype','tax');

        if ( ($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) || ($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead') ) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',array('order_id'=>$this->_tgOrder['order_id'],'status|noequal'=>'3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }

        return $components;
    }

    protected function _canCreate()
    {
        if ('false' == app::get('ome')->getConf('ome.platform.rporder.xiaohongshu') && 'RP' == substr($this->_ordersdf['order_bn'], 0, 2)) {
            $this->__apilog['result']['msg'] = '小红书补发单不接收';
            return false;
        }

        return parent::_canCreate();
    }
}
