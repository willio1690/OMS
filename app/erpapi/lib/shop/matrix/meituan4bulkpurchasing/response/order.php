<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 美团订单
 */
class erpapi_shop_matrix_meituan4bulkpurchasing_response_order extends erpapi_shop_response_order
{
    protected function get_update_components()
    {
        $components = array('markmemo', 'custommemo', 'marktype');
        
        $rs = app::get('ome')->model('order_extend')->getList('extend_status',array('order_id'=>$this->_tgOrder['order_id']));
        // 如果ERP收货人信息未发生变动时，则更新淘宝收货人信息
        if ($rs[0]['extend_status'] != 'consignee_modified') {
            $components[] = 'consignee';
        }
        return $components;
    }
    
    protected function _analysis()
    {
        parent::_analysis();
        $hashCode = kernel::single('ome_security_hash')->get_code();
        if ($this->_ordersdf['extend_field']['oaid']) {
            foreach ($this->_ordersdf['consignee'] as $key => $value) {
                if(strpos($value, '*') !== false) {
                    $this->_ordersdf['consignee'][$key] .= '>>' . $this->_ordersdf['extend_field']['oaid'] . $hashCode;
                }
            }
        }
    }
}