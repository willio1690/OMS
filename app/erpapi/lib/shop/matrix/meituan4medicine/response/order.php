<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 美团医药订单
 */
class erpapi_shop_matrix_meituan4medicine_response_order extends erpapi_shop_response_order
{
    protected function get_update_components()
    {
        $components = array('markmemo', 'custommemo', 'marktype');
        
        //如果没有收货人信息
        if (!$this->_tgOrder['consignee']['name'] || !$this->_tgOrder['consignee']['addr'] || !$this->_tgOrder['consignee']['mobile']) {
            $components[] = 'consignee';
        }
        
        return $components;
    }
    
    protected function _analysis()
    {
        parent::_analysis();
        
        if ($this->_ordersdf['consignee']['telephone'] == '[]') {
            $this->_ordersdf['consignee']['telephone'] = '';
        }
    }
}